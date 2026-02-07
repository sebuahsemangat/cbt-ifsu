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

  window.addEventListener("offline", () => {
    document.getElementById("connection-status").classList.remove("d-none");
  });
  window.addEventListener("online", () => {
    document.getElementById("connection-status").classList.add("d-none");
  });
});

function renderQuestion(index) {
  if (index < 0 || index >= questions.length) return;
  currentIndex = index;
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

  // 3. Question Text
  let qText = q.question_text;
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
      const text = opts[key];
      const checked = myAns === key ? "checked" : "";

      container.innerHTML += `
                <div class="answer-option" onclick="selectOption('${key}', 'pg')">
                    <input type="radio" name="ans_${q.id}" value="${key}" ${checked}>
                    <label><strong>${key}.</strong> ${text}</label>
                </div>
                `;
    });
  } else if (type === "bs") {
    // Table Layout for Benar/Salah
    let html = `
            <div class="table-responsive">
            <table class="table table-bordered table-hover mt-2">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th>Pernyataan</th>
                        <th style="width: 100px;" class="text-center">Benar</th>
                        <th style="width: 100px;" class="text-center">Salah</th>
                    </tr>
                </thead>
                <tbody>`;

    // Support both array of strings or objects with 'text'
    const items = Array.isArray(ansData) ? ansData : [];
    items.forEach((item, idx) => {
      const text = typeof item === "object" && item.text ? item.text : item;
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
  } else {
    const items = Array.isArray(ansData) ? ansData : [];
    items.forEach((item, idx) => {
      let isChecked = false;
      if (Array.isArray(myAns)) {
        // Need to check loose equality if numbers came back as strings from DB JSON
        // But json_decode should handle it.
        isChecked = myAns.includes(idx) || myAns.includes(String(idx));
      }

      container.innerHTML += `
                <div class="answer-option" onclick="toggleMulti(${idx})">
                    <input type="checkbox" name="ans_${q.id}[]" value="${idx}" ${isChecked ? "checked" : ""}>
                    <label>${item.text}</label>
                </div>
                `;
    });
  }

  // 5. Doubt Button
  const doubtVal = stored ? stored.doubt : 0;
  document.getElementById("doubt-checkbox").checked = doubtVal == 1;

  // 6. Navigation Buttons
  document.getElementById("btn-prev").disabled = index === 0;
  document.getElementById("btn-next").innerHTML =
    index === questions.length - 1
      ? "Selesai"
      : 'Berikutnya <i class="bi bi-chevron-right ms-2"></i>';

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
  // Don't trigger if clicked on child input (bubbling)
  if (event.target.tagName === "INPUT") return;

  // If clicking div, verify invalidation
  // Better: let the input click handle logic?
  // But the design requires clicking the whole div.

  // Update local object
  if (!ansMap[q.id]) ansMap[q.id] = { doubt: 0 };
  ansMap[q.id].data = val;

  saveAnswer(q.id, val, ansMap[q.id].doubt);

  const inputs = document.getElementsByName(`ans_${q.id}`);
  inputs.forEach((i) => {
    i.checked = i.value == val;
  });

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

function saveAnswer(qid, data, doubt) {
  fetch("save_answer.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      session_id: sessionId,
      question_id: qid,
      answer_data: data,
      is_doubt: doubt,
    }),
  }).catch((e) => console.error(e));
}

function navStep(step) {
  const next = currentIndex + step;
  if (next >= 0 && next < questions.length) {
    renderQuestion(next);
  } else if (next >= questions.length) {
    finishExam();
  }
}

function updateGrid() {
  const modalGrid = document.getElementById("modal-grid");
  modalGrid.innerHTML = "";
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

    modalGrid.innerHTML += `
            <button class="${classes.join(" ")}" onclick="goTo(${idx})">${idx + 1}</button>
        `;
  });
}

function goTo(idx) {
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

function startTimer() {
  const display = document.getElementById("exam-timer");
  const interval = setInterval(() => {
    remainingSeconds--;
    if (remainingSeconds <= 0) {
      clearInterval(interval);
      alert("Waktu Habis!");
      submitExamForce();
      return;
    }
    // Sync logic could go here

    const h = Math.floor(remainingSeconds / 3600);
    const m = Math.floor((remainingSeconds % 3600) / 60);
    const s = Math.floor(remainingSeconds % 60);
    display.textContent = `${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}`;
  }, 1000);
}

function finishExam() {
  // Hide list modal if open
  const listModalEl = document.getElementById("questionListModal");
  const listModal = bootstrap.Modal.getInstance(listModalEl);
  if (listModal) listModal.hide();

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

function submitExamForce() {
  document.getElementById("submit-form").submit();
}
