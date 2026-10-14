<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';

$action = $_GET['action'] ?? '';

// --- AKSI SIMPAN (TAMBAH / EDIT) ---
if ($action == 'save' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // 1. Ambil Data Dasar
        $id = !empty($_POST['id']) ? $_POST['id'] : null;
        $subject_id = $_POST['subject_id'];
        $reference_id = !empty($_POST['reference_id']) ? $_POST['reference_id'] : null;
        $question_text = $_POST['question_text']; // HTML dari QuillJS
        $type = $_POST['question_type'];
        
        $answer_data = []; // Array penampung struktur jawaban

        // 2. Logika Pengolahan Jawaban Berdasarkan Tipe
        if ($type == 'pg') {
            // --- PILIHAN GANDA (A-E) ---
            $options = $_POST['options'] ?? [];
            $correct = $_POST['correct_ans'] ?? '';

            if (empty($correct)) {
                throw new Exception("Kunci jawaban harus dipilih.");
            }

            // Struktur JSON: { "options": {"A":"Teks A", "B":"Teks B"...}, "correct": "A" }
            $answer_data = [
                'type' => 'single_choice',
                'options' => $options,
                'correct' => $correct
            ];

        } elseif ($type == 'bs') {
            // --- BENAR / SALAH (Multi Item) ---
            $statements = $_POST['statements'] ?? [];
            $ans = $_POST['statement_ans'] ?? [];
            $items = [];

            foreach ($statements as $key => $val) {
                if (!empty(trim($val))) {
                    $items[] = [
                        'text' => trim($val),
                        'is_correct' => (int)($ans[$key] ?? 0) // 1 = Benar, 0 = Salah
                    ];
                }
            }

            if (empty($items)) throw new Exception("Minimal satu pernyataan harus diisi.");

            // Struktur JSON Baru: { "labels": {"pos":"Benar", "neg":"Salah"}, "items": [...] }
            $answer_data = [
                'labels' => [
                    'pos' => trim($_POST['bs_label_pos'] ?? 'Benar'),
                    'neg' => trim($_POST['bs_label_neg'] ?? 'Salah')
                ],
                'items' => $items
            ];

        } elseif ($type == 'multi') {
            // --- PILIHAN GANDA KOMPLEKS (Checkbox) ---
            $opts = $_POST['multi_options'] ?? [];
            
            // Perbaikan Logika:
            // Kita perlu memastikan checkbox terpetakan dengan benar.
            // Form sebelumnya mengirim array, kita asumsikan teks opsi yang diisi adalah penentu.
            // Namun, cara paling aman di backend adalah menyimpan opsi dan statusnya.
            
            // Ambil indeks yang dicentang (perlu penyesuaian sedikit di form agar value checkbox = index array)
            // Jika form mengirim value="1", kita perlu logika lain. 
            // ASUMSI: $_POST['multi_correct'] berisi indeks array yang benar (0, 1, 3 dst)
            // *Lihat catatan di bawah kode ini untuk perbaikan JS form*
            
            $checked_indices = $_POST['multi_correct'] ?? [];
            $items = [];

            foreach ($opts as $index => $val) {
                if (!empty(trim($val))) {
                    // Cek apakah index ini ada di array checked_indices
                    // Kita gunakan in_array karena checkbox mengirim value acak jika tidak diatur index
                    $is_true = in_array($index, $checked_indices) ? 1 : 0;
                    
                    $items[] = [
                        'text' => trim($val),
                        'is_correct' => $is_true
                    ];
                }
            }

            if (empty($items)) throw new Exception("Minimal satu opsi harus diisi.");

            $answer_data = $items;
        } elseif ($type == 'isian') {
            // --- ISIAN SINGKAT ---
            $correct = $_POST['isian_correct'] ?? '';
            if (empty(trim($correct))) {
                throw new Exception("Kunci jawaban isian singkat tidak boleh kosong.");
            }

            $answer_data = [
                'type' => 'short_answer',
                'correct' => trim($correct)
            ];
        }

        // 3. Encode ke JSON
        $json_answer = json_encode($answer_data, JSON_UNESCAPED_UNICODE);

        // 4. Eksekusi Query Database
        if ($id) {
            // Update Data
            $sql = "UPDATE questions SET 
                    subject_id = ?, reference_id = ?, question_text = ?, 
                    question_type = ?, answer_data = ? 
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$subject_id, $reference_id, $question_text, $type, $json_answer, $id]);
        } else {
            // Insert Data Baru
            $sql = "INSERT INTO questions 
                    (subject_id, reference_id, question_text, question_type, answer_data) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$subject_id, $reference_id, $question_text, $type, $json_answer]);
        }

        // Redirect Sukses
        header("Location: ../../dashboard.php?page=questions&subject_id=$subject_id&status=success");

    } catch (Exception $e) {
        // Redirect Error dengan pesan
        $err = urlencode($e->getMessage());
        $sid = $_POST['subject_id'] ?? '';
        header("Location: ../../dashboard.php?page=questions_form&subject_id=$sid&status=error&msg=$err");
    }
    exit;
}

// --- AKSI HAPUS ---
if ($action == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Ambil subject_id dulu untuk redirect
    $stmt = $pdo->prepare("SELECT subject_id FROM questions WHERE id = ?");
    $stmt->execute([$id]);
    $sub = $stmt->fetchColumn();

    if ($sub) {
        $del = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        $del->execute([$id]);
        
        header("Location: ../../dashboard.php?page=questions&subject_id=$sub&status=deleted");
    } else {
        header("Location: ../../dashboard.php?page=exams");
    }
    exit;
}

// --- AKSI BULK UPDATE REFERENSI (MASSAL) ---
if ($action == 'bulk_reference' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    // Cek jika request JSON atau form-data
    $inputJson = json_decode(file_get_contents('php://input'), true);
    $subject_id = $inputJson['subject_id'] ?? $_POST['subject_id'] ?? null;
    $ref_id = $inputJson['reference_id'] ?? $_POST['reference_id'] ?? null;
    $question_ids = $inputJson['question_ids'] ?? $_POST['question_ids'] ?? [];
    $is_ajax = !empty($inputJson) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['is_ajax']);

    try {
        if (!$subject_id) {
            throw new Exception("Parameter subject_id wajib diisi.");
        }

        if (empty($question_ids) || !is_array($question_ids)) {
            throw new Exception("Tidak ada soal yang dipilih.");
        }

        // Sanitasi ID soal
        $clean_question_ids = array_map('intval', $question_ids);
        $clean_question_ids = array_values(array_filter($clean_question_ids, function($v) { return $v > 0; }));
        if (empty($clean_question_ids)) {
            throw new Exception("Daftar ID soal tidak valid.");
        }

        // Cek dan sanitasi referensi
        $target_ref_id = null;
        $ref_title = null;
        $ref_type = null;

        if (!empty($ref_id) && $ref_id !== 'null' && $ref_id !== '0' && $ref_id !== '') {
            $target_ref_id = (int)$ref_id;
            // Validasi kepemilikan referensi terhadap subject_id
            $stmtRefCheck = $pdo->prepare("SELECT id, title, type FROM `references` WHERE id = ? AND subject_id = ?");
            $stmtRefCheck->execute([$target_ref_id, $subject_id]);
            $refData = $stmtRefCheck->fetch();
            if (!$refData) {
                throw new Exception("Referensi yang dipilih tidak ditemukan pada mata pelajaran ini.");
            }
            $ref_title = $refData['title'];
            $ref_type = $refData['type'];
        }

        // Jalankan Update Massal
        $placeholders = implode(',', array_fill(0, count($clean_question_ids), '?'));
        $sql = "UPDATE questions SET reference_id = ? WHERE id IN ($placeholders) AND subject_id = ?";
        $params = array_merge([$target_ref_id], $clean_question_ids, [(int)$subject_id]);
        $stmtUpdate = $pdo->prepare($sql);
        $stmtUpdate->execute($params);

        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'count' => count($clean_question_ids),
                'reference_id' => $target_ref_id,
                'ref_title' => $ref_title,
                'ref_type' => $ref_type,
                'message' => $target_ref_id 
                    ? "Berhasil menerapkan referensi ke " . count($clean_question_ids) . " soal." 
                    : "Berhasil melepas referensi dari " . count($clean_question_ids) . " soal."
            ]);
            exit;
        }

        header("Location: ../../dashboard.php?page=questions&subject_id=$subject_id&status=bulk_success&count=" . count($clean_question_ids));
        exit;

    } catch (Exception $e) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }

        $err = urlencode($e->getMessage());
        header("Location: ../../dashboard.php?page=questions&subject_id=$subject_id&status=error&msg=$err");
        exit;
    }
}