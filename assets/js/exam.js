/**
 * Exam Logic
 * Expects global object EXAM_DATA with:
 * - questions: Array
 * - ansMap: Object
 * - remainingSeconds: Number
 * - sessionId: Number
 */

const questions = EXAM_DATA.questions;
const ansMap = EXAM_DATA.ansMap;
let remainingSeconds = EXAM_DATA.remainingSeconds;
const sessionId = EXAM_DATA.sessionId;

let currentIndex = 0;

document.addEventListener("DOMContentLoaded", () => {
  if (!questions || questions.length === 0) {
    document.getElementById("question-text").textContent =
      "Tidak ada soal untuk mata pelajaran ini.";
    return;
  }
  renderQuestion(0);
  updateGrid();
  startTimer();

  window.addEventListener("pageshow", (event) => {
    if (event.persisted) {
      window.location.reload();
    }
  });

  window.addEventListener("offline", () => {
    updateConnectionStatus(false);
  });
  window.addEventListener("online", () => {
    updateConnectionStatus(true);
  });
});

function updateConnectionStatus(isOnline) {
  const dot = document.querySelector(".status-dot");
  const text = document.querySelector(".status-text");
  if (isOnline) {
    dot.classList.remove("bg-danger");
    dot.classList.add("bg-success");
    text.textContent = "Terhubung";
  } else {
    dot.classList.remove("bg-success");
    dot.classList.add("bg-danger");
    text.textContent = "Terputus";
  }
}

function renderQuestion(index) {
  if (index < 0 || index >= questions.length) return;
  currentIndex = index;

  // Scroll ke atas soal saat navigasi (terutama di mobile)
  window.scrollTo({ top: 0, behavior: "smooth" });

  const q = questions[index];
  const ansData = JSON.parse(q.answer_data); // Parse question structure

  // 1. Update Number
  document.getElementById("current-question-number").textContent = index + 1;

  // 2. Reference Section
  const refCol = document.getElementById("ref-col");
  const qCol = document.getElementById("q-col");
  const refBody = document.getElementById("reference-content-body");

  if (q.ref_data) {
    refCol.style.display = "block";
    qCol.className = "col-md-7";

    let html = "";
    if (q.ref_data.image_path) {
      html += `<img src="../assets/uploads/references/${q.ref_data.image_path}" class="review-img" alt="Ref Image" style="max-height:600px;">`;
    }
    if (q.ref_data.content) {
      html += `<div>${q.ref_data.content}</div>`;
    }
    refBody.innerHTML = html;
  } else {
    refCol.style.display = "none";
    qCol.className = "col-md-12";
  }

  function resolveImgPath(html) {
    if (!html || typeof html !== "string") return html;
    return html.replace(/src=["']uploads\/questions\//g, 'src="../uploads/questions/');
  }

  // 3. Question Text
  let qText = resolveImgPath(q.question_text || "");
  if (q.image_path) {
    qText += `<br><img src="../assets/uploads/questions/${q.image_path}" class="review-img mt-3" style="max-height:200px;">`;
  }
  document.getElementById("question-text").innerHTML = qText;

  // 4. Answers
  const container = document.getElementById("answers-container");
  container.innerHTML = "";

  const stored = ansMap[q.id];
  const myAns = stored ? stored.data : null;

  let type = q.question_type;

  if (type === "pg") {
    const opts = ansData.options || {};
    Object.keys(opts).forEach((key) => {
      const text = resolveImgPath(opts[key]);
      const checked = myAns === key ? "checked" : "";

      container.innerHTML += `
                <div class="answer-option" onclick="selectOption('${key}', 'pg')">
                    <input type="radio" name="ans_${q.id}" value="${key}" ${checked}>
                    <label><strong>${key}.</strong> ${text}</label>
                </div>
                `;
    });
  } else if (type === "bs") {
    // Labels support: Handle legacy array or new object with labels
    const posLabel = ansData.labels ? ansData.labels.pos : "Benar";
    const negLabel = ansData.labels ? ansData.labels.neg : "Salah";
    const items = Array.isArray(ansData) ? ansData : ansData.items || [];

    let html = `
            <div class="table-responsive">
            <table class="table table-bordered table-hover mt-2">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th>Pernyataan</th>
                        <th style="width: 140px;" class="text-center">${posLabel}</th>
                        <th style="width: 140px;" class="text-center">${negLabel}</th>
                    </tr>
                </thead>
                <tbody>`;

    items.forEach((item, idx) => {
      const rawText = typeof item === "object" && item.text ? item.text : item;
      const text = resolveImgPath(rawText);
      const char = String.fromCharCode(65 + idx);

      // myAns expected to be object {index: value}
      // Safe access
      const val =
        myAns && typeof myAns === "object" && myAns[idx] ? myAns[idx] : null;
      const bChecked = val === "B" ? "checked" : "";
      const sChecked = val === "S" ? "checked" : "";

      html += `
                <tr>
                    <td class="text-center fw-bold align-middle">${char}</td>
                    <td class="align-middle">${text}</td>
                    <td class="text-center align-middle">
                        <input class="form-check-input" type="radio" name="bs_${q.id}_${idx}" value="B" ${bChecked} onclick="selectBS(${idx}, 'B')">
                    </td>
                    <td class="text-center align-middle">
                        <input class="form-check-input" type="radio" name="bs_${q.id}_${idx}" value="S" ${sChecked} onclick="selectBS(${idx}, 'S')">
                    </td>
                </tr>
                `;
    });
    html += `</tbody></table></div>`;
    container.innerHTML = html;
  } else if (type === "isian") {
    const val = myAns || "";
    container.innerHTML = `
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body p-4">
                    <label class="form-label fw-bold mb-3"><i class="bi bi-pencil-square me-2"></i>Jawaban Anda:</label>
                    <input type="text" class="form-control form-control-lg border-2" 
                           id="isian_${q.id}" 
                           value="${val}" 
                           placeholder="Tuliskan jawaban singkat di sini..."
                           onkeyup="saveIsian('${q.id}')"
                           autocomplete="off">
                    <div class="mt-2 text-muted small"><i class="bi bi-info-circle me-1"></i>Jawaban otomatis tersimpan saat Anda mengetik.</div>
                </div>
            </div>`;
  } else if (type === "multi") {
    const items = Array.isArray(ansData) ? ansData : [];
    items.forEach((item, idx) => {
      let isChecked = false;
      if (Array.isArray(myAns)) {
        isChecked = myAns.includes(idx) || myAns.includes(String(idx));
      }

      container.innerHTML += `
                <div class="answer-option" onclick="toggleMulti(${idx})">
                    <input type="checkbox" name="ans_${q.id}[]" value="${idx}" ${isChecked ? "checked" : ""}>
                    <label>${resolveImgPath(item.text)}</label>
                </div>
                `;
    });
  }

  // 5. Doubt Button
  const doubtVal = stored ? stored.doubt : 0;
  document.getElementById("doubt-checkbox").checked = doubtVal == 1;

  // 6. Navigation Buttons
  if (typeof updateSavingUI === "function") {
    updateSavingUI();
  } else {
    document.getElementById("btn-prev").disabled = index === 0;
    document.getElementById("btn-next").innerHTML =
      index === questions.length - 1
        ? "Selesai"
        : 'Berikutnya <i class="bi bi-chevron-right ms-2"></i>';
  }

  updateGrid();
}

function selectBS(idx, val) {
  const q = questions[currentIndex];
  // Ensure data structure
  if (!ansMap[q.id]) ansMap[q.id] = { doubt: 0 };

  let currData = ansMap[q.id].data;
  // If not object (e.g. null or old array format), reset
  if (
    typeof currData !== "object" ||
    currData === null ||
    Array.isArray(currData)
  ) {
    currData = {};
  }

  currData[idx] = val;
  ansMap[q.id].data = currData;

  saveAnswer(q.id, currData, ansMap[q.id].doubt);
  updateGrid();
}

function selectOption(val, type) {
  const q = questions[currentIndex];

  // Update local object
  if (!ansMap[q.id]) ansMap[q.id] = { doubt: 0 };
  ansMap[q.id].data = val;

  // Sync radio buttons visual state
  const inputs = document.getElementsByName(`ans_${q.id}`);
  inputs.forEach((i) => {
    i.checked = i.value == val;
  });

  saveAnswer(q.id, val, ansMap[q.id].doubt);
  updateGrid();
}

function toggleMulti(idx) {
  if (event.target.tagName === "INPUT") {
    // If input clicked directly, just update Model
  } else {
    // If div clicked, toggle input
    const input = document.getElementsByName(
      `ans_${questions[currentIndex].id}[]`,
    )[idx];
    input.checked = !input.checked;
  }

  const q = questions[currentIndex];
  if (!ansMap[q.id]) ansMap[q.id] = { doubt: 0, data: [] };
  let arr = ansMap[q.id].data || [];
  if (!Array.isArray(arr)) arr = [];

  // Re-read from DOM to be safe? Or Logic?
  // Let's rely on Logic
  const checkbox = document.getElementsByName(`ans_${q.id}[]`)[idx];
  const isChecked = checkbox.checked;

  // Update array
  const iIdx = parseInt(idx);
  if (isChecked) {
    if (!arr.includes(iIdx)) arr.push(iIdx);
  } else {
    arr = arr.filter((i) => i !== iIdx);
  }

  ansMap[q.id].data = arr;
  saveAnswer(q.id, arr, ansMap[q.id].doubt);
  updateGrid();
}

function toggleDoubt(e) {
  const q = questions[currentIndex];
  const cb = document.getElementById("doubt-checkbox");

  // Toggle the checkbox if the click was on the button background/icon,
  // but let the checkbox handle itself if it was clicked directly.
  if (e && e.target !== cb) {
    cb.checked = !cb.checked;
  }

  const isDoubt = cb.checked ? 1 : 0;

  // Ensure we have an entry in ansMap for this question
  if (!ansMap[q.id]) {
    ansMap[q.id] = { data: null, doubt: 0 };
  }

  ansMap[q.id].doubt = isDoubt;

  saveAnswer(q.id, ansMap[q.id].data, isDoubt);
  updateGrid();
}

// Per-question save queue: prevents race conditions on rapid answer changes
const _saveTimers = {};
const _pendingSavesSet = new Set();

function updateSavingUI() {
  const isSaving = _pendingSavesSet.size > 0;

  const btnPrev = document.getElementById("btn-prev");
  const btnNext = document.getElementById("btn-next");

  if (isSaving) {
    if (btnPrev) btnPrev.disabled = true;
    if (btnNext) {
      btnNext.disabled = true;
      btnNext.innerHTML =
        '<span class="spinner-border spinner-border-sm align-middle me-2" role="status" aria-hidden="true"></span>Menyimpan...';
    }
  } else {
    if (btnPrev) btnPrev.disabled = currentIndex === 0;
    if (btnNext) {
      btnNext.disabled = false;
      btnNext.innerHTML =
        currentIndex === questions.length - 1
          ? "Selesai"
          : 'Berikutnya <i class="bi bi-chevron-right ms-2"></i>';
    }
  }
}

function saveAnswer(qid, data, doubt) {
  // Debounce: if called multiple times quickly (e.g. isian typing),
  // cancel previously scheduled save and reschedule after 400ms
  if (_saveTimers[qid]) clearTimeout(_saveTimers[qid]);

  _pendingSavesSet.add(qid);
  updateSavingUI();

  _saveTimers[qid] = setTimeout(() => {
    delete _saveTimers[qid];
    _doSaveAnswer(qid, data, doubt, 0);
  }, 400);
}

function _doSaveAnswer(qid, data, doubt, retryCount) {
  fetch("save_answer.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      session_id: sessionId,
      question_id: qid,
      answer_data: data,
      is_doubt: doubt,
      remaining_seconds: remainingSeconds
    }),
  })
    .then((res) => {
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
      }
      return res.json();
    })
    .then((json) => {
      if (json.error) {
        console.error("Save error from server:", json.error);
        // Retry once after 1.5s
        if (retryCount < 1) {
          setTimeout(
            () => _doSaveAnswer(qid, data, doubt, retryCount + 1),
            1500,
          );
        } else {
          _pendingSavesSet.delete(qid);
          updateSavingUI();
          updateGrid();
        }
      } else {
        _pendingSavesSet.delete(qid);
        updateSavingUI();
        updateGrid();
      }
    })
    .catch((e) => {
      console.error("Network error saat menyimpan jawaban:", e);
      // Retry once after 2s on network failure
      if (retryCount < 1) {
        setTimeout(() => _doSaveAnswer(qid, data, doubt, retryCount + 1), 2000);
      } else {
        _pendingSavesSet.delete(qid);
        updateSavingUI();
        updateGrid();
      }
    });
}

function saveIsian(qid) {
  const val = document.getElementById(`isian_${qid}`).value;
  const q = questions[currentIndex];

  if (!ansMap[q.id]) ansMap[q.id] = { doubt: 0 };
  ansMap[q.id].data = val;

  saveAnswer(q.id, val, ansMap[q.id].doubt);
  updateGrid();
}

function navStep(step) {
  if (typeof _pendingSavesSet !== "undefined" && _pendingSavesSet.size > 0)
    return;
  const next = currentIndex + step;
  if (next >= 0 && next < questions.length) {
    renderQuestion(next);
  } else if (next >= questions.length) {
    finishExam();
  }
}

function updateGrid() {
  const modalGrid = document.getElementById("modal-grid");
  if (!modalGrid) return;
  modalGrid.innerHTML = "";

  const isSaving =
    typeof _pendingSavesSet !== "undefined" && _pendingSavesSet.size > 0;

  questions.forEach((q, idx) => {
    const hasAns =
      ansMap[q.id] &&
      ansMap[q.id].data !== null &&
      (Array.isArray(ansMap[q.id].data)
        ? ansMap[q.id].data.length > 0
        : true) &&
      ansMap[q.id].data !== "" &&
      ansMap[q.id].data !== undefined;

    const isDoubt = ansMap[q.id] && ansMap[q.id].doubt == 1;
    const isCurr = idx === currentIndex;

    let classes = ["btn", "question-number-btn"];

    if (isCurr) classes.push("current");

    if (isDoubt) {
      classes.push("doubt");
    } else if (hasAns) {
      classes.push("answered");
    } else {
      classes.push("btn-outline-secondary");
    }

    let disabledAttr = isSaving ? "disabled" : "";

    modalGrid.innerHTML += `
            <button class="${classes.join(" ")}" ${disabledAttr} onclick="goTo(${idx})">${idx + 1}</button>
        `;
  });
}

function goTo(idx) {
  if (typeof _pendingSavesSet !== "undefined" && _pendingSavesSet.size > 0)
    return;
  const el = document.getElementById("questionListModal");
  const m = bootstrap.Modal.getInstance(el);
  if (m) m.hide();
  renderQuestion(idx);
}

function setFontSize(size) {
  const mainContent = document.getElementById("main-content");
  mainContent.classList.remove("font-small", "font-medium", "font-large");
  mainContent.classList.add("font-" + size);
  document
    .querySelectorAll(".font-size-btn")
    .forEach((b) => b.classList.remove("active"));
  event.target.closest("button").classList.add("active");
}

let _isSyncingTime = false;
let _lastSyncedSeconds = null;

function syncRemainingTime(force = false) {
  if (typeof remainingSeconds === "undefined" || remainingSeconds === null || !sessionId) return;
  if (!force && _lastSyncedSeconds === remainingSeconds) return;

  _lastSyncedSeconds = remainingSeconds;
  _isSyncingTime = true;

  fetch("sync_time.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      session_id: sessionId,
      remaining_seconds: remainingSeconds
    })
  })
    .then((res) => res.json())
    .then((data) => {
      // Optional logging for debugging
      // console.log("[CBT Sync OK]", data);
    })
    .catch((err) => {
      // console.warn("[CBT Sync Err]", err);
    })
    .finally(() => {
      _isSyncingTime = false;
    });
}

function startTimer() {
  const display = document.getElementById("exam-timer");
  
  // Guard: Jika halaman direload saat waktu sudah habis dari perhitungan server,
  // kumpulkan otomatis secara diam-diam tanpa memunculkan alert berulang.
  if (remainingSeconds <= 0) {
    display.textContent = "00:00:00";
    submitExamForce();
    return;
  }

  // Sync pertama kali saat ujian dibuka
  syncRemainingTime(true);

  const interval = setInterval(() => {
    remainingSeconds--;
    if (remainingSeconds <= 0) {
      clearInterval(interval);
      display.textContent = "00:00:00";
      syncRemainingTime(true);
      alert("Waktu Habis! Mengumpulkan jawaban otomatis.");
      submitExamForce();
      return;
    }

    // Sync berkala ke server setiap 3 detik agar waktu tersimpan jika komputer mati mendadak
    if (remainingSeconds % 3 === 0) {
      syncRemainingTime();
    }

    const h = Math.floor(remainingSeconds / 3600);
    const m = Math.floor((remainingSeconds % 3600) / 60);
    const s = Math.floor(remainingSeconds % 60);
    display.textContent = `${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}`;
  }, 1000);
}

// Simpan sisa waktu saat halaman ditutup atau siswa beralih tab
window.addEventListener("beforeunload", () => {
  if (typeof remainingSeconds !== "undefined" && remainingSeconds !== null && sessionId) {
    const params = new URLSearchParams();
    params.append("session_id", sessionId);
    params.append("remaining_seconds", remainingSeconds);

    if (navigator.sendBeacon) {
      navigator.sendBeacon("sync_time.php", params);
    } else {
      fetch("sync_time.php", {
        method: "POST",
        body: params,
        keepalive: true
      }).catch(() => {});
    }
  }
});

document.addEventListener("visibilitychange", () => {
  if (document.visibilityState === "hidden") {
    syncRemainingTime(true);
  }
});

function finishExam(isFromNav = false) {
  if (typeof _pendingSavesSet !== "undefined" && _pendingSavesSet.size > 0)
    return;

  // Cek batas minimal durasi pengerjaan
  const totalDurationSeconds =
    typeof EXAM_DATA !== "undefined" && EXAM_DATA.totalDurationSeconds
      ? EXAM_DATA.totalDurationSeconds
      : 0;
  const minDurationSeconds =
    typeof EXAM_DATA !== "undefined" && EXAM_DATA.minDurationSeconds
      ? EXAM_DATA.minDurationSeconds
      : 0;
  const minDurationMinutes =
    typeof EXAM_DATA !== "undefined" && EXAM_DATA.minDurationMinutes
      ? EXAM_DATA.minDurationMinutes
      : 0;
  const minDurationPercent =
    typeof EXAM_DATA !== "undefined" && EXAM_DATA.minDurationPercent
      ? EXAM_DATA.minDurationPercent
      : 0;

  // Waktu yang sudah berjalan
  const elapsedSeconds = Math.max(0, totalDurationSeconds - remainingSeconds);

  // Jika batas minimal diaktifkan (> 0), sisa waktu utama belum habis (> 0), dan waktu berjalan belum capai minimal
  if (
    minDurationSeconds > 0 &&
    remainingSeconds > 0 &&
    elapsedSeconds < minDurationSeconds
  ) {
    // Hide list modal jika sedang terbuka
    const listModalEl = document.getElementById("questionListModal");
    if (listModalEl) {
      const listModal = bootstrap.Modal.getInstance(listModalEl);
      if (listModal) listModal.hide();
    }

    const waitSeconds = minDurationSeconds - elapsedSeconds;
    const waitM = Math.floor(waitSeconds / 60);
    const waitS = waitSeconds % 60;
    const waitStr =
      waitM > 0 ? `${waitM} menit ${waitS} detik` : `${waitS} detik`;

    const elapsedM = Math.floor(elapsedSeconds / 60);
    const elapsedS = elapsedSeconds % 60;
    const elapsedStr =
      elapsedM > 0 ? `${elapsedM} menit ${elapsedS} detik` : `${elapsedS} detik`;

    const totalM = Math.floor(totalDurationSeconds / 60);

    const msgEl = document.getElementById("min-duration-msg");
    if (msgEl) {
      msgEl.innerHTML = `Siswa belum diizinkan mengumpulkan jawaban sebelum batas minimal pengerjaan <strong>${minDurationMinutes} menit</strong> (${minDurationPercent}%) terlewati.`;
    }
    const elTotal = document.getElementById("md-total-dur");
    if (elTotal) elTotal.textContent = `${totalM} Menit`;
    const elMin = document.getElementById("md-min-dur");
    if (elMin) elMin.textContent = `${minDurationMinutes} Menit`;
    const elElapsed = document.getElementById("md-elapsed-dur");
    if (elElapsed) elElapsed.textContent = elapsedStr;
    const elWait = document.getElementById("md-remaining-wait");
    if (elWait) elWait.textContent = waitStr;

    const warnModalEl = document.getElementById("minDurationWarningModal");
    if (warnModalEl) {
      const warnModal = new bootstrap.Modal(warnModalEl);
      warnModal.show();
    } else {
      alert(
        `Belum dapat mengumpulkan jawaban!\nBatas minimal pengerjaan adalah ${minDurationMinutes} menit (${minDurationPercent}%).\nSisa waktu tunggu: ${waitStr}`
      );
    }
    return;
  }

  // Hide list modal if open
  const listModalEl = document.getElementById("questionListModal");
  if (listModalEl) {
    const listModal = bootstrap.Modal.getInstance(listModalEl);
    if (listModal) listModal.hide();
  }

  // Count doubts
  let doubts = 0;
  questions.forEach((q) => {
    if (ansMap[q.id] && ansMap[q.id].doubt == 1) {
      doubts++;
    }
  });

  const doubtWarning = document.getElementById("doubt-warning");
  const doubtCount = document.getElementById("doubt-count");

  if (doubts > 0) {
    doubtWarning.classList.remove("d-none");
    doubtCount.textContent = doubts;
  } else {
    doubtWarning.classList.add("d-none");
  }

  // Show confirmation modal
  const confirmModal = new bootstrap.Modal(
    document.getElementById("confirmFinishModal"),
  );
  confirmModal.show();
}

let _isExamSubmitting = false;

function submitExamForce() {
  _isExamSubmitting = true;
  document.getElementById("submit-form").submit();
}

// =========================================================================
// FITUR ANTI-CHEATING (ANTI-KECURANGAN)
// =========================================================================

let _lastViolationTime = 0;
let _isSessionLocked = false;
let _lockPollInterval = null;
let _violationGracePeriodUntil = Date.now() + 3000;

function initAntiCheating() {
  if (typeof EXAM_DATA === "undefined" || !EXAM_DATA.enableAntiCheating) return;

  _isSessionLocked = !!EXAM_DATA.isLocked;
  _violationGracePeriodUntil = Date.now() + 3000;

  // 1. Blokir Klik Kanan
  document.addEventListener("contextmenu", (e) => {
    e.preventDefault();
  });

  // 2. Blokir Copy, Cut, Paste
  document.addEventListener("copy", (e) => {
    e.preventDefault();
  });
  document.addEventListener("cut", (e) => {
    e.preventDefault();
  });
  document.addEventListener("paste", (e) => {
    e.preventDefault();
  });

  // 3. Blokir Drag & Drop
  document.addEventListener("dragstart", (e) => {
    e.preventDefault();
  });

  // 4. Blokir Shortcut Keyboard Terlarang
  document.addEventListener("keydown", (e) => {
    // F12 (DevTools)
    if (e.key === "F12") {
      e.preventDefault();
      return false;
    }

    if (e.ctrlKey || e.metaKey) {
      const key = e.key.toLowerCase();
      // Ctrl+C, Ctrl+V, Ctrl+X, Ctrl+A, Ctrl+U (source), Ctrl+P (print)
      if (["c", "v", "x", "a", "u", "p"].includes(key)) {
        e.preventDefault();
        return false;
      }
      // Ctrl+Shift+I / J / C (Inspect Element)
      if (e.shiftKey && ["i", "j", "c"].includes(key)) {
        e.preventDefault();
        return false;
      }
    }
  });

  // 5. Deteksi Pindah Tab / Minimize (visibilitychange - standar Android & iOS)
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "hidden") {
      triggerViolation("Pindah Tab / Minimize Browser");
    }
  });

  // 6. Khusus Mobile & iOS Safari: event pagehide saat beralih aplikasi / tab di HP
  window.addEventListener("pagehide", () => {
    triggerViolation("Beralih Aplikasi / Tab Browser");
  });

  // 7. Deteksi Kehilangan Fokus / Alt+Tab / Buka Aplikasi Lain (window blur)
  window.addEventListener("blur", () => {
    // Delay 250ms untuk verifikasi agar tidak salah deteksi saat transisi modal atau render
    setTimeout(() => {
      if (!document.hasFocus() && !_isSessionLocked && !_isExamSubmitting && Date.now() >= _violationGracePeriodUntil) {
        triggerViolation("Beralih Aplikasi / Jendela");
      }
    }, 250);
  });

  // Jika saat buka halaman status sudah terkunci, langsung kunci
  if (_isSessionLocked) {
    lockExamSession();
  }
}


function triggerViolation(reason) {
  if (typeof EXAM_DATA === "undefined" || !EXAM_DATA.enableAntiCheating) return;
  if (typeof remainingSeconds !== "undefined" && remainingSeconds <= 0) return;
  if (_isExamSubmitting || _isSessionLocked) return;

  const now = Date.now();
  // Masa tenggang (cooldown/grace period) setelah load, unlock, atau menutup modal
  if (now < _violationGracePeriodUntil) return;

  // Debounce 3.5 detik agar satu aksi beralih aplikasi tidak dihitung berkali-kali
  if (now - _lastViolationTime < 3500) return;
  _lastViolationTime = now;

  const params = new URLSearchParams();
  params.append("session_id", sessionId);
  params.append("reason", reason || "Pelanggaran Integritas");

  fetch("record_violation.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: params.toString(),
    keepalive: true
  })

    .then((res) => res.json())
    .then((data) => {
      if (data.success && data.anti_cheating) {
        const count = data.violation_count;
        const isLocked = data.is_locked;

        if (isLocked || count >= 3) {
          lockExamSession();
        } else {
          showViolationWarning(count);
        }
      }
    })
    .catch(() => {});
}

function showViolationWarning(count) {
  const currentStrikeEl = document.getElementById("viol-current-strike");
  const remainingStrikesEl = document.getElementById("viol-remaining-strikes");
  if (currentStrikeEl) currentStrikeEl.textContent = count;
  if (remainingStrikesEl) remainingStrikesEl.textContent = Math.max(0, 3 - count);

  const warnModalEl = document.getElementById("violationWarningModal");
  if (warnModalEl) {
    const warnModal = bootstrap.Modal.getOrCreateInstance(warnModalEl);
    warnModal.show();
  } else {
    alert(
      `PERINGATAN PELANGGARAN (${count}/3)!\nAnda terdeteksi meninggalkan halaman ujian. Sisa kesempatan: ${3 - count} kali lagi sebelum ujian terkunci.`
    );
  }
}

function acknowledgeViolation() {
  _violationGracePeriodUntil = Date.now() + 3000;
  const warnModalEl = document.getElementById("violationWarningModal");
  if (warnModalEl) {
    const warnModal = bootstrap.Modal.getInstance(warnModalEl);
    if (warnModal) warnModal.hide();
  }
}

function dismissUnlockModal() {
  _violationGracePeriodUntil = Date.now() + 4000;
  const unlockedModalEl = document.getElementById("examUnlockedModal");
  if (unlockedModalEl) {
    const modal = bootstrap.Modal.getInstance(unlockedModalEl);
    if (modal) modal.hide();
  }
}

function lockExamSession() {
  _isSessionLocked = true;

  // Tutup semua modal lain jika terbuka
  const warnModalEl = document.getElementById("violationWarningModal");
  if (warnModalEl) {
    const wm = bootstrap.Modal.getInstance(warnModalEl);
    if (wm) wm.hide();
  }
  const confModalEl = document.getElementById("confirmFinishModal");
  if (confModalEl) {
    const cm = bootstrap.Modal.getInstance(confModalEl);
    if (cm) cm.hide();
  }
  const listModalEl = document.getElementById("questionListModal");
  if (listModalEl) {
    const lm = bootstrap.Modal.getInstance(listModalEl);
    if (lm) lm.hide();
  }

  // Tampilkan overlay layar terkunci
  const overlay = document.getElementById("examLockedOverlay");
  if (overlay) {
    overlay.classList.remove("d-none");
  }

  // Polling berkala setiap 3 detik untuk mengecek apakah admin/pengawas sudah membuka kunci
  if (!_lockPollInterval) {
    _lockPollInterval = setInterval(checkLockStatusPoll, 3000);
  }
}

function checkLockStatusPoll() {
  if (!_isSessionLocked) {
    if (_lockPollInterval) clearInterval(_lockPollInterval);
    return;
  }

  fetch(`check_lock_status.php?session_id=${sessionId}`)
    .then((res) => res.json())
    .then((data) => {
      if (data.success && !data.is_locked) {
        unlockSessionSuccess();
      }
    })
    .catch(() => {});
}

function submitProctorUnlock(e) {
  e.preventDefault();
  const passInput = document.getElementById("proctorPasswordInput");
  const errDiv = document.getElementById("proctorUnlockError");
  const btn = document.getElementById("btnProctorUnlock");

  if (!passInput || !passInput.value.trim()) return;

  if (errDiv) errDiv.classList.add("d-none");
  if (btn) btn.disabled = true;

  const params = new URLSearchParams();
  params.append("session_id", sessionId);
  params.append("admin_password", passInput.value.trim());

  fetch("unlock_session.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: params.toString()
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        passInput.value = "";
        unlockSessionSuccess();
      } else {
        if (errDiv) {
          errDiv.textContent = data.message || "Password pengawas salah!";
          errDiv.classList.remove("d-none");
        }
      }
    })
    .catch(() => {
      if (errDiv) {
        errDiv.textContent = "Terjadi kesalahan koneksi ke server.";
        errDiv.classList.remove("d-none");
      }
    })
    .finally(() => {
      if (btn) btn.disabled = false;
    });
}

function unlockSessionSuccess() {
  _isSessionLocked = false;
  // Berikan masa tenggang 7 detik setelah unlock agar tidak terpicu event blur saat transisi
  _violationGracePeriodUntil = Date.now() + 7000;
  _lastViolationTime = Date.now();

  if (_lockPollInterval) {
    clearInterval(_lockPollInterval);
    _lockPollInterval = null;
  }

  const overlay = document.getElementById("examLockedOverlay");
  if (overlay) {
    overlay.classList.add("d-none");
  }

  // Reset counter indikator pelanggaran di UI jika modal peringatan sebelumnya sempat aktif
  const currentStrikeEl = document.getElementById("viol-current-strike");
  const remainingStrikesEl = document.getElementById("viol-remaining-strikes");
  if (currentStrikeEl) currentStrikeEl.textContent = "0";
  if (remainingStrikesEl) remainingStrikesEl.textContent = "3";

  // Tampilkan modal bahwa akun telah dibuka (non-blocking, tidak memicu browser blur)
  const unlockedModalEl = document.getElementById("examUnlockedModal");
  if (unlockedModalEl && typeof bootstrap !== "undefined") {
    const modal = bootstrap.Modal.getOrCreateInstance(unlockedModalEl);
    modal.show();
  }
}

// Inisialisasi fitur saat halaman selesai dimuat
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initAntiCheating);
} else {
  initAntiCheating();
}


