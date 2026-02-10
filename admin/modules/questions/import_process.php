<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';
require_once '../../../vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Image;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../dashboard.php?page=exams");
    exit;
}

$subject_id = $_POST['subject_id'] ?? null;
$reference_id = !empty($_POST['reference_id']) ? $_POST['reference_id'] : null;

if (!$subject_id) {
    header("Location: ../../dashboard.php?page=exams&status=error&msg=" . urlencode("Subject ID tidak valid"));
    exit;
}

// Validate file upload
if (!isset($_FILES['word_file']) || $_FILES['word_file']['error'] !== UPLOAD_ERR_OK) {
    $errorMsg = "Gagal mengupload file.";
    header("Location: ../../dashboard.php?page=questions_import&subject_id=$subject_id&status=error&msg=" . urlencode($errorMsg));
    exit;
}

$file = $_FILES['word_file'];
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if ($extension !== 'docx') {
    header("Location: ../../dashboard.php?page=questions_import&subject_id=$subject_id&status=error&msg=" . urlencode("Hanya file .docx yang diperbolehkan"));
    exit;
}

// Create uploads directory for question images
$uploadDir = '../../../uploads/questions/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

try {
    // Load Word document
    $phpWord = IOFactory::load($file['tmp_name']);
    
    // Extract all text content with images
    $fullContent = [];
    $imageCounter = 0;
    
    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {
            $paragraphContent = extractElementContent($element, $uploadDir, $imageCounter);
            if (!empty(trim($paragraphContent))) {
                $fullContent[] = $paragraphContent;
            }
        }
    }
    
    // Join all paragraphs
    $documentText = implode("\n", $fullContent);
    
    // Parse questions from document
    $questions = parseQuestions($documentText);
    
    if (empty($questions)) {
        throw new Exception("Tidak ditemukan soal dalam dokumen. Pastikan format sesuai template.");
    }
    
    // Insert questions to database
    $insertedCount = 0;
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO questions (subject_id, reference_id, question_text, question_type, answer_data) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($questions as $q) {
            $answerJson = json_encode($q['answer_data'], JSON_UNESCAPED_UNICODE);
            $stmt->execute([
                $subject_id,
                $reference_id,
                $q['question_text'],
                $q['question_type'],
                $answerJson
            ]);
            $insertedCount++;
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
    // Success redirect
    header("Location: ../../dashboard.php?page=questions_import&subject_id=$subject_id&status=success&count=$insertedCount");
    
} catch (Exception $e) {
    header("Location: ../../dashboard.php?page=questions_import&subject_id=$subject_id&status=error&msg=" . urlencode($e->getMessage()));
}
exit;

// ================== HELPER FUNCTIONS ==================

/**
 * Extract content from PHPWord element (handles Text, TextRun, Image)
 */
function extractElementContent($element, $uploadDir, &$imageCounter) {
    $content = '';
    
    if ($element instanceof Text) {
        $content = $element->getText();
    } elseif ($element instanceof TextRun) {
        foreach ($element->getElements() as $child) {
            if ($child instanceof Text) {
                $content .= $child->getText();
            } elseif ($child instanceof Image) {
                $imagePath = saveImage($child, $uploadDir, $imageCounter);
                if ($imagePath) {
                    $content .= '<img src="' . $imagePath . '" class="img-fluid">';
                }
            }
        }
    } elseif ($element instanceof Image) {
        $imagePath = saveImage($element, $uploadDir, $imageCounter);
        if ($imagePath) {
            $content = '<img src="' . $imagePath . '" class="img-fluid">';
        }
    } elseif (method_exists($element, 'getElements')) {
        foreach ($element->getElements() as $child) {
            $content .= extractElementContent($child, $uploadDir, $imageCounter);
        }
    } elseif (method_exists($element, 'getText')) {
        $content = $element->getText();
    }
    
    return $content;
}

/**
 * Save embedded image from Word document
 */
function saveImage($imageElement, $uploadDir, &$imageCounter) {
    try {
        $imageData = $imageElement->getImageStringData(true);
        if (empty($imageData)) {
            // Try getting from source
            $source = $imageElement->getSource();
            if (file_exists($source)) {
                $imageData = base64_encode(file_get_contents($source));
            }
        }
        
        if (!empty($imageData)) {
            $imageCounter++;
            $imageName = 'img_' . time() . '_' . $imageCounter . '.png';
            $imagePath = $uploadDir . $imageName;
            
            file_put_contents($imagePath, base64_decode($imageData));
            
            // Return relative path for storage in DB
            return 'uploads/questions/' . $imageName;
        }
    } catch (Exception $e) {
        // Skip image on error
    }
    return null;
}

/**
 * Parse document text into structured questions
 */
function parseQuestions($text) {
    $questions = [];
    
    // Split by question markers
    $pattern = '/\[(SOAL|SOAL-BS|SOAL-MULTI)\]/i';
    $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    
    $i = 0;
    while ($i < count($parts)) {
        $marker = strtoupper(trim($parts[$i]));
        $content = isset($parts[$i + 1]) ? trim($parts[$i + 1]) : '';
        
        if ($marker === 'SOAL') {
            $q = parsePGQuestion($content);
            if ($q) $questions[] = $q;
            $i += 2;
        } elseif ($marker === 'SOAL-BS') {
            $q = parseBSQuestion($content);
            if ($q) $questions[] = $q;
            $i += 2;
        } elseif ($marker === 'SOAL-MULTI') {
            $q = parseMultiQuestion($content);
            if ($q) $questions[] = $q;
            $i += 2;
        } else {
            $i++;
        }
    }
    
    return $questions;
}

/**
 * Parse Pilihan Ganda (PG) question
 */
function parsePGQuestion($content) {
    $lines = array_filter(array_map('trim', explode("\n", $content)));
    
    $questionText = '';
    $options = ['A' => '', 'B' => '', 'C' => '', 'D' => '', 'E' => ''];
    $correct = '';
    
    $currentSection = 'question';
    $questionLines = [];
    
    foreach ($lines as $line) {
        // Check for option markers
        if (preg_match('/^\[([A-E])\]\s*(.*)$/i', $line, $match)) {
            $letter = strtoupper($match[1]);
            $options[$letter] = trim($match[2]);
            $currentSection = 'options';
        }
        // Check for answer marker
        elseif (preg_match('/^\[JAWABAN\]\s*([A-E])/i', $line, $match)) {
            $correct = strtoupper($match[1]);
        }
        // Part of question text
        elseif ($currentSection === 'question') {
            $questionLines[] = $line;
        }
    }
    
    $questionText = implode('<br>', $questionLines);
    
    if (empty($questionText) || empty($correct)) {
        return null;
    }
    
    return [
        'question_text' => $questionText,
        'question_type' => 'pg',
        'answer_data' => [
            'type' => 'single_choice',
            'options' => $options,
            'correct' => $correct
        ]
    ];
}

/**
 * Parse Benar/Salah (BS) question
 */
function parseBSQuestion($content) {
    $lines = array_filter(array_map('trim', explode("\n", $content)));
    
    $questionLines = [];
    $statements = [];
    $foundStatement = false;
    
    foreach ($lines as $line) {
        // Check for statement pattern: [1] text [BENAR] or [SALAH]
        if (preg_match('/^\[(\d+)\]\s*(.*?)\s*\[(BENAR|SALAH)\]\s*$/i', $line, $match)) {
            $statements[] = [
                'text' => trim($match[2]),
                'is_correct' => strtoupper($match[3]) === 'BENAR' ? 1 : 0
            ];
            $foundStatement = true;
        }
        // Part of main question (before statements)
        elseif (!$foundStatement) {
            $questionLines[] = $line;
        }
    }
    
    $questionText = implode('<br>', $questionLines);
    
    if (empty($questionText) || empty($statements)) {
        return null;
    }
    
    return [
        'question_text' => $questionText,
        'question_type' => 'bs',
        'answer_data' => $statements
    ];
}

/**
 * Parse Multi Checkbox question
 */
function parseMultiQuestion($content) {
    $lines = array_filter(array_map('trim', explode("\n", $content)));
    
    $questionLines = [];
    $options = [];
    $foundOption = false;
    
    foreach ($lines as $line) {
        // Check for option pattern: [1] text [V] or []
        if (preg_match('/^\[(\d+)\]\s*(.*?)\s*\[(V?)\]\s*$/i', $line, $match)) {
            $options[] = [
                'text' => trim($match[2]),
                'is_correct' => !empty($match[3]) ? 1 : 0
            ];
            $foundOption = true;
        }
        // Part of main question (before options)
        elseif (!$foundOption) {
            $questionLines[] = $line;
        }
    }
    
    $questionText = implode('<br>', $questionLines);
    
    if (empty($questionText) || empty($options)) {
        return null;
    }
    
    return [
        'question_text' => $questionText,
        'question_type' => 'multi',
        'answer_data' => $options
    ];
}
