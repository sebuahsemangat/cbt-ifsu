<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';
require_once '../../../vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Image;
use PhpOffice\PhpWord\Element\ListItemRun;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\Table;

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

// Validate file upload (Mendukung Base64 untuk bypass OpenResty 403 pada multipart upload)
$filePathToLoad = null;
$isTempFile = false;
$tempFilePath = null;

if (!empty($_POST['word_base64'])) {
    $dataUri = $_POST['word_base64'];
    $base64Data = (strpos($dataUri, ',') !== false) ? substr($dataUri, strpos($dataUri, ',') + 1) : $dataUri;
    $binaryData = base64_decode($base64Data);

    if ($binaryData === false || strlen($binaryData) < 10) {
        $errorMsg = "File Word Base64 tidak valid atau rusak.";
        header("Location: ../../dashboard.php?page=questions_import&subject_id=$subject_id&status=error&msg=" . urlencode($errorMsg));
        exit;
    }

    $tempFilePath = tempnam(sys_get_temp_dir(), 'word_import_') . '.docx';
    file_put_contents($tempFilePath, $binaryData);
    $filePathToLoad = $tempFilePath;
    $isTempFile = true;

} elseif (isset($_FILES['word_file']) && $_FILES['word_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['word_file'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($extension !== 'docx') {
        header("Location: ../../dashboard.php?page=questions_import&subject_id=$subject_id&status=error&msg=" . urlencode("Hanya file .docx yang diperbolehkan"));
        exit;
    }
    $filePathToLoad = $file['tmp_name'];
} else {
    $errorMsg = "Gagal mengupload file Word.";
    header("Location: ../../dashboard.php?page=questions_import&subject_id=$subject_id&status=error&msg=" . urlencode($errorMsg));
    exit;
}

// Create uploads directory for question images
$uploadDir = '../../../uploads/questions/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

try {
    // Load Word document
    $phpWord = IOFactory::load($filePathToLoad);
    
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
    
    // Parse questions & references from document
    $parsedData = parseQuestionsAndReferences($documentText, $reference_id);
    $questions = $parsedData['questions'];
    $referencesFromDoc = $parsedData['references'];
    
    if (empty($questions)) {
        throw new Exception("Tidak ditemukan soal dalam dokumen. Pastikan format sesuai template.");
    }
    
    // Insert references & questions to database inside transaction
    $insertedCount = 0;
    $refCreatedCount = 0;
    $pdo->beginTransaction();
    
    try {
        $refKeyToId = [];
        $targetRefDir = '../../../assets/uploads/references/';
        if (!is_dir($targetRefDir)) {
            @mkdir($targetRefDir, 0755, true);
        }

        // 1. Simpan Referensi dari dokumen Word
        foreach ($referencesFromDoc as $refKey => $refItem) {
            $imagePath = null;
            if (!empty($refItem['image_base64'])) {
                $binary = base64_decode($refItem['image_base64']);
                if ($binary) {
                    $imgName = 'ref_' . time() . '_' . uniqid() . '.' . $refItem['image_ext'];
                    file_put_contents($targetRefDir . $imgName, $binary);
                    $imagePath = $imgName;
                }
            }

            $refType = !empty($refItem['content']) ? 'text' : 'image';
            $refContent = $refItem['content'];

            // Cek apakah sudah ada referensi dengan judul persis sama pada mapel ini
            $chk = $pdo->prepare("SELECT id, image_path FROM `references` WHERE subject_id = ? AND title = ?");
            $chk->execute([$subject_id, $refItem['title']]);
            $existing = $chk->fetch();

            if ($existing) {
                $refId = $existing['id'];
                if ($imagePath && empty($existing['image_path'])) {
                    $pdo->prepare("UPDATE `references` SET image_path = ? WHERE id = ?")->execute([$imagePath, $refId]);
                }
            } else {
                $insRef = $pdo->prepare("INSERT INTO `references` (subject_id, title, type, content, image_path) VALUES (?, ?, ?, ?, ?)");
                $insRef->execute([$subject_id, $refItem['title'], $refType, $refContent, $imagePath]);
                $refId = $pdo->lastInsertId();
                $refCreatedCount++;
            }

            $refKeyToId[$refKey] = $refId;
        }

        // 2. Simpan Soal
        $stmt = $pdo->prepare("INSERT INTO questions (subject_id, reference_id, question_text, question_type, answer_data) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($questions as $q) {
            $qRefId = null;
            if (!empty($q['ref_key']) && isset($refKeyToId[$q['ref_key']])) {
                $qRefId = $refKeyToId[$q['ref_key']];
            } else {
                $qRefId = $reference_id; // Fallback jika tidak ada referensi inline
            }

            $answerJson = json_encode($q['answer_data'], JSON_UNESCAPED_UNICODE);
            $stmt->execute([
                $subject_id,
                $qRefId,
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
    
    // JSON Success response
    $msg = 'Berhasil mengimport ' . $insertedCount . ' soal';
    if ($refCreatedCount > 0) {
        $msg .= ' dan membuat ' . $refCreatedCount . ' referensi stimulus baru.';
    } else {
        $msg .= '.';
    }

    echo json_encode([
        'status' => 'success',
        'count' => $insertedCount,
        'ref_count' => $refCreatedCount,
        'msg' => $msg
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage()
    ]);
} finally {
    if (!empty($isTempFile) && !empty($tempFilePath) && file_exists($tempFilePath)) {
        @unlink($tempFilePath);
    }
}
exit;

// ================== HELPER FUNCTIONS ==================

/**
 * Extract content from PHPWord element (handles Text, TextRun, ListItemRun, ListItem, Image)
 */
function extractElementContent($element, $uploadDir, &$imageCounter) {
    $content = '';
    
    // Handle bullet / list items from Word document
    if ($element instanceof ListItemRun || $element instanceof ListItem) {
        $inner = '';
        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $child) {
                $inner .= extractElementContent($child, $uploadDir, $imageCounter);
            }
        } elseif (method_exists($element, 'getText')) {
            $inner = $element->getText();
        }
        $inner = trim($inner);
        if ($inner !== '') {
            $content = '• ' . $inner;
        }
        return $content;
    }
    
    // Handle Table from Word document
    if ($element instanceof Table) {
        $tableHtml = '<div class="table-responsive my-2"><table class="table table-bordered table-stimulus my-0" style="border-collapse: collapse; width: auto; min-width: 50%; margin: 0 auto; border: 1px solid #cbd5e1;"><tbody>';
        $rows = $element->getRows();
        $isFirstRow = true;
        foreach ($rows as $row) {
            $tableHtml .= '<tr>';
            foreach ($row->getCells() as $cell) {
                $cellHtml = '';
                foreach ($cell->getElements() as $child) {
                    $cellHtml .= extractElementContent($child, $uploadDir, $imageCounter);
                }
                $cellHtml = trim($cellHtml);
                $tag = $isFirstRow ? 'th' : 'td';
                $extraStyle = $isFirstRow ? 'background-color: #f8fafc; font-weight: 600;' : '';
                $tableHtml .= '<' . $tag . ' style="border: 1px solid #cbd5e1; padding: 6px 12px; text-align: center; vertical-align: middle; ' . $extraStyle . '">' . $cellHtml . '</' . $tag . '>';
            }
            $tableHtml .= '</tr>';
            $isFirstRow = false;
        }
        $tableHtml .= '</tbody></table></div>';
        return $tableHtml;
    }

    if ($element instanceof Text) {
        $content = $element->getText();
    } elseif ($element instanceof TextRun) {
        foreach ($element->getElements() as $child) {
            if ($child instanceof Text) {
                $content .= $child->getText();
            } elseif ($child instanceof Image) {
                $imageSrc = saveImage($child, $uploadDir, $imageCounter);
                if ($imageSrc) {
                    $content .= '<img src="' . $imageSrc . '" class="img-fluid" style="max-height: 250px;">';
                }
            } else {
                $content .= extractElementContent($child, $uploadDir, $imageCounter);
            }
        }
    } elseif ($element instanceof Image) {
        $imageSrc = saveImage($element, $uploadDir, $imageCounter);
        if ($imageSrc) {
            $content = '<img src="' . $imageSrc . '" class="img-fluid" style="max-height: 250px;">';
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
 * Format document HTML: convert plain newlines to <br> while preserving HTML tables & block elements
 */
function formatDocumentHtml($html) {
    if (empty($html)) return '';
    $lines = explode("\n", $html);
    $formattedLines = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '') continue;
        if (preg_match('/^<(?:div|table|tbody|thead|tr|td|th|p|h[1-6]|ul|ol|li|blockquote)/i', $trimmed)) {
            $formattedLines[] = $trimmed;
        } else {
            $formattedLines[] = $trimmed . '<br>';
        }
    }
    $result = implode("\n", $formattedLines);
    $result = preg_replace('/<br>\s*(<(?:div|table|tbody|thead|tr|td|th|p))/i', '$1', $result);
    $result = preg_replace('/(?:<br\s*\/?>\s*)+$/i', '', $result);
    return trim($result);
}

/**
 * Save embedded image from Word document as disk file & return Data URI for seamless display
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
            $rawBinary = base64_decode($imageData);
            $mime = 'image/png';
            if (str_starts_with($rawBinary, "\xFF\xD8\xFF")) {
                $mime = 'image/jpeg';
            } elseif (str_starts_with($rawBinary, "GIF")) {
                $mime = 'image/gif';
            } elseif (str_starts_with($rawBinary, "RIFF") && strpos($rawBinary, "WEBP") !== false) {
                $mime = 'image/webp';
            }
            
            $ext = ($mime === 'image/jpeg') ? 'jpg' : (($mime === 'image/gif') ? 'gif' : 'png');
            $imageCounter++;
            $imageName = 'img_' . time() . '_' . $imageCounter . '.' . $ext;
            $imagePath = $uploadDir . $imageName;
            
            // Simpan file ke server untuk cadangan fisik
            file_put_contents($imagePath, $rawBinary);
            
            // Kembalikan Base64 Data URI agar gambar 100% tampil di halaman admin (edit/view) maupun halaman ujian siswa tanpa terpengaruh direktori
            return 'data:' . $mime . ';base64,' . $imageData;
        }
    } catch (Exception $e) {
        // Skip image on error
    }
    return null;
}

/**
 * Parse document text into structured questions and references
 */
function parseQuestionsAndReferences($text, $defaultRefId = null) {
    $questions = [];
    $references = [];
    $currentRefKey = null;
    $refCounter = 0;
    
    // Split by question & reference markers
    $pattern = '/\[\s*(SOAL|SOAL-BS|SOAL-MULTI|SOAL-ISIAN|TANPA-REFERENSI|NO-REFERENSI|REFERENSI(?:\s*:\s*[^\n\]]+)?)\s*\]/i';
    $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    
    $i = 0;
    while ($i < count($parts)) {
        $marker = trim($parts[$i]);
        $content = isset($parts[$i + 1]) ? trim($parts[$i + 1]) : '';
        
        // 1. Tag Referensi: [REFERENSI: Judul] atau [REFERENSI]
        if (preg_match('/^REFERENSI(?:\s*:\s*(.+))?$/i', $marker, $mRef)) {
            $refCounter++;
            $refKey = 'ref_' . $refCounter;
            $refTitle = isset($mRef[1]) ? trim($mRef[1]) : '';
            
            // Ambil judul dari baris pertama jika tidak ada di dalam tag
            if (empty($refTitle)) {
                $lines = explode("\n", $content);
                if (!empty($lines) && preg_match('/^(?:Judul|Nama|Title)\s*:\s*(.+)$/i', trim($lines[0]), $mTitle)) {
                    $refTitle = trim($mTitle[1]);
                    array_shift($lines);
                    $content = implode("\n", $lines);
                }
            }
            
            if (empty($refTitle)) {
                $cleanPreview = trim(strip_tags($content));
                $cleanPreview = preg_replace('/\s+/', ' ', $cleanPreview);
                if (!empty($cleanPreview)) {
                    $refTitle = mb_substr($cleanPreview, 0, 40) . (mb_strlen($cleanPreview) > 40 ? '...' : '');
                } else {
                    $refTitle = 'Referensi ' . $refCounter;
                }
            }
            
            $refTitle = mb_substr($refTitle, 0, 250);
            
            // Cek apakah ada gambar di dalam konten referensi
            $refImageBase64 = null;
            $refImageExt = 'jpg';
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $mImg)) {
                $src = $mImg[1];
                if (preg_match('/^data:image\/(\w+);base64,(.+)$/s', $src, $mB64)) {
                    $rawExt = strtolower($mB64[1]);
                    $refImageExt = ($rawExt === 'jpeg') ? 'jpg' : $rawExt;
                    $refImageBase64 = $mB64[2];
                }
            }
            
            // Bersihkan teks (hapus tag <img> jika ada agar gambar tidak dobel karena sudah masuk image_path)
            $textOnly = trim(preg_replace('/<img[^>]+>/i', '', $content));
            $formattedContent = formatDocumentHtml($textOnly);
            
            $references[$refKey] = [
                'title' => $refTitle,
                'image_base64' => $refImageBase64,
                'image_ext' => $refImageExt,
                'content' => (!empty(strip_tags($formattedContent)) || strpos($formattedContent, '<table') !== false) ? $formattedContent : null,
                'has_image' => ($refImageBase64 !== null)
            ];
            
            $currentRefKey = $refKey;
            $i += 2;
            continue;
        }
        
        // 2. Tag Tanpa Referensi: [TANPA-REFERENSI]
        if (strcasecmp($marker, 'TANPA-REFERENSI') === 0 || strcasecmp($marker, 'NO-REFERENSI') === 0) {
            $currentRefKey = null;
            $i += 2;
            continue;
        }
        
        // 3. Tag Soal
        $upperMarker = strtoupper($marker);
        $q = null;
        if ($upperMarker === 'SOAL') {
            $q = parsePGQuestion($content);
        } elseif ($upperMarker === 'SOAL-BS') {
            $q = parseBSQuestion($content);
        } elseif ($upperMarker === 'SOAL-MULTI') {
            $q = parseMultiQuestion($content);
        } elseif ($upperMarker === 'SOAL-ISIAN') {
            $q = parseIsianQuestion($content);
        }
        
        if ($q) {
            $q['ref_key'] = $currentRefKey;
            $questions[] = $q;
            $i += 2;
        } else {
            $i++;
        }
    }
    
    return [
        'questions' => $questions,
        'references' => $references
    ];
}

/**
 * Parse Pilihan Ganda (PG) question (supports inline image & multiline options)
 */
function parsePGQuestion($content) {
    $lines = array_filter(array_map('trim', explode("\n", $content)));
    
    $questionText = '';
    $options = ['A' => '', 'B' => '', 'C' => '', 'D' => '', 'E' => ''];
    $correct = '';
    
    $currentSection = 'question';
    $currentOption = '';
    $questionLines = [];
    
    foreach ($lines as $line) {
        // Check for option markers: [A] text/image or [A] alone
        if (preg_match('/^\[([A-E])\]\s*(.*)$/i', $line, $match)) {
            $letter = strtoupper($match[1]);
            $options[$letter] = trim($match[2]);
            $currentSection = 'options';
            $currentOption = $letter;
        }
        // Check for answer marker
        elseif (preg_match('/^\[JAWABAN\]\s*([A-E])/i', $line, $match)) {
            $correct = strtoupper($match[1]);
            $currentSection = 'answer';
        }
        // Part of option text or image on separate line below [A]
        elseif ($currentSection === 'options' && !empty($currentOption)) {
            $options[$currentOption] .= ($options[$currentOption] !== '' ? '<br>' : '') . $line;
        }
        // Part of question text
        elseif ($currentSection === 'question') {
            $questionLines[] = $line;
        }
    }
    
    $questionText = implode('<br>', $questionLines);
    $questionText = preg_replace('/<br>\s*(<(?:div|table|tbody|thead|tr|td|th))/i', '$1', $questionText);
    $questionText = preg_replace('/(<\/(?:div|table|tbody|thead|tr|td|th)>)\s*<br>/i', '$1', $questionText);
    
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
/**
 * Parse Jawaban Singkat (Isian) question
 */
function parseIsianQuestion($content) {
    $lines = array_filter(array_map('trim', explode("\n", $content)));
    
    $questionLines = [];
    $correct = '';
    
    foreach ($lines as $line) {
        // Check for answer marker
        if (preg_match('/^\[JAWABAN\]\s*(.*)$/i', $line, $match)) {
            $correct = trim($match[1]);
        }
        // Part of question text
        else {
            $questionLines[] = $line;
        }
    }
    
    $questionText = implode('<br>', $questionLines);
    
    if (empty($questionText) || empty($correct)) {
        return null;
    }
    
    return [
        'question_text' => $questionText,
        'question_type' => 'isian',
        'answer_data' => [
            'type' => 'short_answer',
            'correct' => $correct
        ]
    ];
}
