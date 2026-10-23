<?php
ob_start();
libxml_use_internal_errors(true);

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

/**
 * Kirim respon JSON bersih bebas dari warning/output tak terduga
 */
function sendJsonResponse($data, $statusCode = 200) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../dashboard.php?page=exams");
    exit;
}

// Mendukung payload raw JSON jika dikirim via Content-Type: application/json
if (empty($_POST) && $rawInput = file_get_contents('php://input')) {
    $jsonData = json_decode($rawInput, true);
    if (is_array($jsonData)) {
        $_POST = $jsonData;
    }
}

$subject_id = $_POST['subject_id'] ?? null;
$reference_id = !empty($_POST['reference_id']) ? $_POST['reference_id'] : null;

if (!$subject_id) {
    sendJsonResponse(['status' => 'error', 'msg' => 'Subject ID tidak valid.'], 400);
}

$action = $_POST['action'] ?? 'preview'; // 'preview', 'confirm_save', or 'direct_import'

// =========================================================================
// ACTION: CONFIRM_SAVE (Simpan soal yang telah divalidasi oleh guru ke DB)
// =========================================================================
if ($action === 'confirm_save') {
    $questionsJson = $_POST['questions_json'] ?? '';
    $referencesJson = $_POST['references_json'] ?? '';
    
    $questions = json_decode($questionsJson, true);
    $referencesFromDoc = json_decode($referencesJson, true) ?? [];
    
    if (empty($questions) || !is_array($questions)) {
        sendJsonResponse(['status' => 'error', 'msg' => 'Tidak ada soal yang dipilih untuk disimpan.'], 400);
    }
    
    $insertedCount = 0;
    $refCreatedCount = 0;
    $pdo->beginTransaction();
    
    try {
        $refKeyToId = [];
        $targetRefDir = '../../../assets/uploads/references/';
        if (!is_dir($targetRefDir)) {
            @mkdir($targetRefDir, 0755, true);
        }

        // 1. Simpan Referensi dari dokumen
        foreach ($referencesFromDoc as $refKey => $refItem) {
            $imagePath = null;
            if (!empty($refItem['image_base64'])) {
                $binary = base64_decode($refItem['image_base64']);
                if ($binary) {
                    $imgName = 'ref_' . time() . '_' . uniqid() . '.' . ($refItem['image_ext'] ?? 'jpg');
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

            $answerJson = is_string($q['answer_data']) ? $q['answer_data'] : json_encode($q['answer_data'], JSON_UNESCAPED_UNICODE);
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
        
        $msg = 'Berhasil menyimpan ' . $insertedCount . ' soal';
        if ($refCreatedCount > 0) {
            $msg .= ' dan membuat ' . $refCreatedCount . ' referensi stimulus baru.';
        } else {
            $msg .= '.';
        }

        sendJsonResponse([
            'status' => 'success',
            'count' => $insertedCount,
            'ref_count' => $refCreatedCount,
            'msg' => $msg
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        sendJsonResponse(['status' => 'error', 'msg' => $e->getMessage()], 400);
    }
}

// =========================================================================
// ACTION: PREVIEW (Default) atau DIRECT_IMPORT (Load File Word)
// =========================================================================
$filePathToLoad = null;
$isTempFile = false;
$tempFilePath = null;

if (!empty($_POST['word_base64'])) {
    $dataUri = $_POST['word_base64'];
    $base64Data = (strpos($dataUri, ',') !== false) ? substr($dataUri, strpos($dataUri, ',') + 1) : $dataUri;
    $binaryData = base64_decode($base64Data);

    if ($binaryData === false || strlen($binaryData) < 10) {
        sendJsonResponse(['status' => 'error', 'msg' => 'File Word Base64 tidak valid atau rusak.'], 400);
    }

    $tempFilePath = tempnam(sys_get_temp_dir(), 'word_import_') . '.docx';
    file_put_contents($tempFilePath, $binaryData);
    $filePathToLoad = $tempFilePath;
    $isTempFile = true;

} elseif (isset($_FILES['word_file']) && $_FILES['word_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['word_file'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($extension !== 'docx') {
        sendJsonResponse(['status' => 'error', 'msg' => 'Hanya file .docx yang diperbolehkan.'], 400);
    }
    $filePathToLoad = $file['tmp_name'];
} else {
    sendJsonResponse(['status' => 'error', 'msg' => 'Gagal mengupload file Word. Berkas tidak ditemukan.'], 400);
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
    
    // Bersihkan tag HTML formatting yang mungkin membungkus marker sistem seperti [SOAL], [REFERENSI]
    $documentText = preg_replace_callback('/(?:<[a-z0-9]+[^>]*>|\s)*\[(?:<[a-z0-9]+[^>]*>|\s)*(SOAL|SOAL-BS|SOAL-MULTI|SOAL-ISIAN|TANPA-REFERENSI|NO-REFERENSI|REFERENSI(?:\s*:\s*[^\n\]]+)?)(?:<\/[a-z0-9]+>|\s)*\](?:<\/[a-z0-9]+>|\s)*/i', function($m) {
        return "\n[" . strip_tags($m[1]) . "]\n";
    }, $documentText);
    
    // Parse questions & references from document
    $parsedData = parseQuestionsAndReferences($documentText, $reference_id);
    $questions = $parsedData['questions'];
    $referencesFromDoc = $parsedData['references'];
    
    if (empty($questions)) {
        throw new Exception("Tidak ditemukan soal dalam dokumen. Pastikan tag [SOAL], [SOAL-BS], dll. ada dalam dokumen.");
    }
    
    // Hitung validasi soal
    $validCount = 0;
    $warningCount = 0;
    foreach ($questions as $q) {
        if (!empty($q['is_valid'])) {
            $validCount++;
        } else {
            $warningCount++;
        }
    }

    // JIKA ACTION = PREVIEW (Default): Kembalikan data soal untuk divalidasi oleh guru
    if ($action === 'preview') {
        sendJsonResponse([
            'status' => 'preview',
            'total_questions' => count($questions),
            'valid_count' => $validCount,
            'warning_count' => $warningCount,
            'total_references' => count($referencesFromDoc),
            'questions' => $questions,
            'references' => $referencesFromDoc
        ]);
    }

    // JIKA ACTION = DIRECT_IMPORT (Bypass preview jika diminta)
    $insertedCount = 0;
    $refCreatedCount = 0;
    $pdo->beginTransaction();
    
    try {
        $refKeyToId = [];
        $targetRefDir = '../../../assets/uploads/references/';
        if (!is_dir($targetRefDir)) {
            @mkdir($targetRefDir, 0755, true);
        }

        foreach ($referencesFromDoc as $refKey => $refItem) {
            $imagePath = null;
            if (!empty($refItem['image_base64'])) {
                $binary = base64_decode($refItem['image_base64']);
                if ($binary) {
                    $imgName = 'ref_' . time() . '_' . uniqid() . '.' . ($refItem['image_ext'] ?? 'jpg');
                    file_put_contents($targetRefDir . $imgName, $binary);
                    $imagePath = $imgName;
                }
            }

            $refType = !empty($refItem['content']) ? 'text' : 'image';
            $refContent = $refItem['content'];

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

        $stmt = $pdo->prepare("INSERT INTO questions (subject_id, reference_id, question_text, question_type, answer_data) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($questions as $q) {
            $qRefId = null;
            if (!empty($q['ref_key']) && isset($refKeyToId[$q['ref_key']])) {
                $qRefId = $refKeyToId[$q['ref_key']];
            } else {
                $qRefId = $reference_id;
            }

            $answerJson = is_string($q['answer_data']) ? $q['answer_data'] : json_encode($q['answer_data'], JSON_UNESCAPED_UNICODE);
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
    
    $msg = 'Berhasil mengimport ' . $insertedCount . ' soal';
    if ($refCreatedCount > 0) {
        $msg .= ' dan membuat ' . $refCreatedCount . ' referensi stimulus baru.';
    } else {
        $msg .= '.';
    }

    sendJsonResponse([
        'status' => 'success',
        'count' => $insertedCount,
        'ref_count' => $refCreatedCount,
        'msg' => $msg
    ]);
    
} catch (Exception $e) {
    sendJsonResponse([
        'status' => 'error',
        'msg' => $e->getMessage()
    ], 400);
} finally {
    if (!empty($isTempFile) && !empty($tempFilePath) && file_exists($tempFilePath)) {
        @unlink($tempFilePath);
    }
}
exit;

// ================== HELPER FUNCTIONS ==================

/**
 * Format text element with font styles (subscript, superscript)
 */
function formatFormattedText($textElement) {
    if (!($textElement instanceof Text)) {
        if (is_object($textElement) && method_exists($textElement, 'getText')) {
            return $textElement->getText();
        }
        return '';
    }
    
    $text = $textElement->getText();
    if ($text === '' || $text === null) {
        return '';
    }
    
    $font = method_exists($textElement, 'getFontStyle') ? $textElement->getFontStyle() : null;
    if ($font && is_object($font)) {
        $isSub = method_exists($font, 'isSubScript') && $font->isSubScript();
        $isSuper = method_exists($font, 'isSuperScript') && $font->isSuperScript();
        $isBold = method_exists($font, 'isBold') && $font->isBold();
        $isItalic = method_exists($font, 'isItalic') && $font->isItalic();
        $underline = method_exists($font, 'getUnderline') ? $font->getUnderline() : null;
        $isUnderline = !empty($underline) && $underline !== 'none';
        
        // Bungkus subscript / superscript terlebih dahulu
        if ($isSub) {
            $text = '<sub>' . $text . '</sub>';
        } elseif ($isSuper) {
            $text = '<sup>' . $text . '</sup>';
        }

        // Bungkus formatting (underline, italic, bold) jika bukan spasi kosong saja
        if (trim($text) !== '') {
            if ($isUnderline) {
                $text = '<u>' . $text . '</u>';
            }
            if ($isItalic) {
                $text = '<em>' . $text . '</em>';
            }
            if ($isBold) {
                $text = '<strong>' . $text . '</strong>';
            }
        }
    }
    return $text;
}

/**
 * Extract content from PHPWord element (handles Text, TextRun, ListItemRun, ListItem, Image, Table)
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
            $inner = formatFormattedText($element);
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
        $content = formatFormattedText($element);
    } elseif ($element instanceof TextRun) {
        foreach ($element->getElements() as $child) {
            if ($child instanceof Text) {
                $content .= formatFormattedText($child);
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
        $content = formatFormattedText($element);
    }
    
    // Gabungkan tag formatting berurutan (misal <strong>kata</strong><strong>kunci</strong> atau <sub>2</sub><sub>0</sub>)
    $content = preg_replace('/<\/sub>(\s*)<sub>/i', '$1', $content);
    $content = preg_replace('/<\/sup>(\s*)<sup>/i', '$1', $content);
    $content = preg_replace('/<\/strong>(\s*)<strong>/i', '$1', $content);
    $content = preg_replace('/<\/b>(\s*)<b>/i', '$1', $content);
    $content = preg_replace('/<\/em>(\s*)<em>/i', '$1', $content);
    $content = preg_replace('/<\/i>(\s*)<i>/i', '$1', $content);
    $content = preg_replace('/<\/u>(\s*)<u>/i', '$1', $content);

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
            
            file_put_contents($imagePath, $rawBinary);
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
            
            $textOnly = trim(preg_replace('/<img[^>]+>/i', '', $content));
            $formattedContent = formatDocumentHtml($textOnly);
            
            $references[$refKey] = [
                'key' => $refKey,
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
            $q['temp_id'] = 'q_' . (count($questions) + 1);
            $q['number'] = count($questions) + 1;
            $q['ref_title'] = ($currentRefKey && isset($references[$currentRefKey])) ? $references[$currentRefKey]['title'] : null;
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
 * Parse Pilihan Ganda (PG) question with full validation
 */
function parsePGQuestion($content) {
    $lines = array_filter(array_map('trim', explode("\n", $content)));
    
    $options = ['A' => '', 'B' => '', 'C' => '', 'D' => '', 'E' => ''];
    $correct = '';
    
    $currentSection = 'question';
    $currentOption = '';
    $questionLines = [];
    
    foreach ($lines as $line) {
        if (preg_match('/^(?:<[^>]+>)*\[([A-E])\](?:<\/[^>]+>)*\s*(.*)$/i', $line, $match)) {
            $letter = strtoupper($match[1]);
            $options[$letter] = trim($match[2]);
            $currentSection = 'options';
            $currentOption = $letter;
        } elseif (preg_match('/^(?:<[^>]+>)*\[JAWABAN\](?:<\/[^>]+>)*\s*(?:<[^>]+>)*([A-E])/i', $line, $match)) {
            $correct = strtoupper($match[1]);
            $currentSection = 'answer';
        } elseif ($currentSection === 'options' && !empty($currentOption)) {
            $options[$currentOption] .= ($options[$currentOption] !== '' ? '<br>' : '') . $line;
        } elseif ($currentSection === 'question') {
            $questionLines[] = $line;
        }
    }
    
    $questionText = implode('<br>', $questionLines);
    $questionText = preg_replace('/<br>\s*(<(?:div|table|tbody|thead|tr|td|th))/i', '$1', $questionText);
    $questionText = preg_replace('/(<\/(?:div|table|tbody|thead|tr|td|th)>)\s*<br>/i', '$1', $questionText);
    $questionText = trim($questionText);
    
    // Filter non-empty options
    $filledOptions = array_filter($options, function($v) {
        return trim(strip_tags($v, '<img>')) !== '';
    });
    
    $warnings = [];
    if (empty($questionText)) {
        $warnings[] = 'Teks pertanyaan kosong.';
    }
    if (count($filledOptions) < 2) {
        $warnings[] = 'Pilihan jawaban kurang lengkap (hanya terdeteksi ' . count($filledOptions) . ' pilihan).';
    }
    if (empty($correct)) {
        $warnings[] = 'Kunci jawaban belum ditentukan.';
    } elseif (!isset($options[$correct]) || empty(trim($options[$correct]))) {
        $warnings[] = "Kunci jawaban [$correct] dipilih, tapi pilihan tersebut kosong.";
    }
    
    if (empty($questionText) && count($filledOptions) === 0 && empty($correct)) {
        return null;
    }
    
    return [
        'question_text' => $questionText,
        'question_type' => 'pg',
        'question_type_label' => 'Pilihan Ganda',
        'answer_data' => [
            'type' => 'single_choice',
            'options' => $options,
            'correct' => $correct
        ],
        'warnings' => $warnings,
        'is_valid' => empty($warnings)
    ];
}

/**
 * Parse Benar/Salah (BS) question with full validation
 */
function parseBSQuestion($content) {
    $lines = array_filter(array_map('trim', explode("\n", $content)));
    
    $questionLines = [];
    $statements = [];
    $foundStatement = false;
    
    foreach ($lines as $line) {
        if (preg_match('/^(?:<[^>]+>)*\[(\d+)\](?:<\/[^>]+>)*\s*(.*?)\s*(?:<[^>]+>)*\[(BENAR|SALAH)\](?:<\/[^>]+>)*\s*$/i', $line, $match)) {
            $statements[] = [
                'text' => trim($match[2]),
                'is_correct' => strtoupper($match[3]) === 'BENAR' ? 1 : 0
            ];
            $foundStatement = true;
        } elseif (!$foundStatement) {
            $questionLines[] = $line;
        }
    }
    
    $questionText = trim(implode('<br>', $questionLines));
    $warnings = [];
    if (empty($questionText)) {
        $warnings[] = 'Teks pertanyaan kosong.';
    }
    if (empty($statements)) {
        $warnings[] = 'Belum ada pernyataan [1] ... [BENAR/SALAH] yang terdeteksi.';
    }
    
    if (empty($questionText) && empty($statements)) {
        return null;
    }
    
    return [
        'question_text' => $questionText,
        'question_type' => 'bs',
        'question_type_label' => 'Benar / Salah',
        'answer_data' => $statements,
        'warnings' => $warnings,
        'is_valid' => empty($warnings)
    ];
}

/**
 * Parse Multi Checkbox question with full validation
 */
function parseMultiQuestion($content) {
    $lines = array_filter(array_map('trim', explode("\n", $content)));
    
    $questionLines = [];
    $options = [];
    $foundOption = false;
    $hasCorrect = false;
    
    foreach ($lines as $line) {
        if (preg_match('/^(?:<[^>]+>)*\[(\d+)\](?:<\/[^>]+>)*\s*(.*?)\s*(?:<[^>]+>)*\[(V?)\](?:<\/[^>]+>)*\s*$/i', $line, $match)) {
            $isCorrect = !empty($match[3]) ? 1 : 0;
            if ($isCorrect) $hasCorrect = true;
            $options[] = [
                'text' => trim($match[2]),
                'is_correct' => $isCorrect
            ];
            $foundOption = true;
        } elseif (!$foundOption) {
            $questionLines[] = $line;
        }
    }
    
    $questionText = trim(implode('<br>', $questionLines));
    $warnings = [];
    if (empty($questionText)) {
        $warnings[] = 'Teks pertanyaan kosong.';
    }
    if (empty($options)) {
        $warnings[] = 'Belum ada opsi pilihan [1] ... [V] yang terdeteksi.';
    } elseif (!$hasCorrect) {
        $warnings[] = 'Belum ada opsi jawaban benar yang ditandai dengan [V].';
    }
    
    if (empty($questionText) && empty($options)) {
        return null;
    }
    
    return [
        'question_text' => $questionText,
        'question_type' => 'multi',
        'question_type_label' => 'Pilihan Ganda Kompleks',
        'answer_data' => $options,
        'warnings' => $warnings,
        'is_valid' => empty($warnings)
    ];
}

/**
 * Parse Jawaban Singkat (Isian) question with full validation
 */
function parseIsianQuestion($content) {
    $lines = array_filter(array_map('trim', explode("\n", $content)));
    
    $questionLines = [];
    $correct = '';
    
    foreach ($lines as $line) {
        if (preg_match('/^(?:<[^>]+>)*\[(?:JAWABAN|KUNCI)\](?:<\/[^>]+>)*\s*(.*)$/i', $line, $match)) {
            $correct = trim(strip_tags($match[1]));
        } else {
            $questionLines[] = $line;
        }
    }
    
    $questionText = trim(implode('<br>', $questionLines));
    $warnings = [];
    if (empty($questionText)) {
        $warnings[] = 'Teks pertanyaan kosong.';
    }
    if ($correct === '') {
        $warnings[] = 'Kunci jawaban singkat belum ditentukan [JAWABAN].';
    }
    
    if (empty($questionText) && $correct === '') {
        return null;
    }
    
    return [
        'question_text' => $questionText,
        'question_type' => 'isian',
        'question_type_label' => 'Jawaban Singkat',
        'answer_data' => [
            'type' => 'short_answer',
            'correct' => $correct
        ],
        'warnings' => $warnings,
        'is_valid' => empty($warnings)
    ];
}
