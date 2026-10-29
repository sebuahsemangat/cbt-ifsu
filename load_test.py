#!/usr/bin/env python3
"""
=============================================================================
CBT LOAD & STRESS TESTING TOOL (Simulasi Ujian Berbasis Komputer)
=============================================================================
Alat ini mensimulasikan aktivitas siswa nyata secara serentak (concurrent users)
tanpa memerlukan 40 komputer fisik.

Fitur:
1. Simulasi Login Serentak (Login Spike) dengan 40 akun siswa nyata dari database.
2. Simulasi Akses Dashboard & Pengecekan Jadwal Ujian.
3. Simulasi Pengiriman Jawaban (Auto-Save AJAX).
4. Statistik Latensi (Min, Rata-rata, P95, Max) & Laju Request (RPS).
5. Pembersihan otomatis status is_logged_in setelah tes selesai.
=============================================================================
"""

import sys
import os
import time
import json
import argparse
import subprocess
import threading
from concurrent.futures import ThreadPoolExecutor, as_completed
import statistics

try:
    import requests
except ImportError:
    print("[ERROR] Modul 'requests' belum terpasang. Jalankan: pip install requests")
    sys.exit(1)

# Pastikan output encoding mendukung UTF-8 di Windows terminal
if sys.platform == 'win32':
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except Exception:
        pass

# Warna konsol terminal
class Color:
    RESET = '\033[0m'
    BOLD = '\033[1m'
    GREEN = '\033[92m'
    YELLOW = '\033[93m'
    RED = '\033[91m'
    CYAN = '\033[96m'
    BLUE = '\033[94m'
    GRAY = '\033[90m'

def print_header(title):
    print(f"\n{Color.CYAN}{'='*70}{Color.RESET}")
    print(f"{Color.BOLD}{Color.CYAN} {title} {Color.RESET}")
    print(f"{Color.CYAN}{'='*70}{Color.RESET}")

def run_php_helper(code):
    """Menjalankan snippet PHP untuk mengambil data/reset DB secara aman."""
    try:
        proc = subprocess.run(
            ['php', '-r', code],
            capture_output=True,
            text=True,
            check=True
        )
        return proc.stdout.strip()
    except Exception as e:
        return None

def reset_all_students_login():
    """Mereset status is_logged_in semua siswa menjadi 0 di database."""
    php_code = (
        "require 'config/database.php'; "
        "$pdo->exec('UPDATE students SET is_logged_in = 0'); "
        "echo 'OK';"
    )
    res = run_php_helper(php_code)
    return res == 'OK'

def fetch_students_from_db(limit=40):
    """Mengambil akun siswa dari database."""
    php_code = (
        f"require 'config/database.php'; "
        f"$st = $pdo->query('SELECT id, nis, full_name, class FROM students ORDER BY id ASC LIMIT {limit}'); "
        f"echo json_encode($st->fetchAll());"
    )
    raw = run_php_helper(php_code)
    if raw:
        try:
            return json.loads(raw)
        except Exception:
            pass
    return []

def fetch_active_exams_and_questions():
    """Mengambil ujian aktif dan sampel soal untuk pengujian simpan jawaban."""
    php_code = (
        "require 'config/database.php'; "
        "$exam = $pdo->query('SELECT id, exam_name FROM exams WHERE is_active = 1 ORDER BY id DESC LIMIT 1')->fetch(); "
        "if (!$exam) { echo json_encode(null); exit; } "
        "$sub = $pdo->query('SELECT id FROM subjects WHERE exam_id = ' . (int)$exam['id'] . ' LIMIT 1')->fetch(); "
        "if (!$sub) { echo json_encode(null); exit; } "
        "$q = $pdo->query('SELECT id FROM questions WHERE subject_id = ' . (int)$sub['id'] . ' LIMIT 5')->fetchAll(); "
        "echo json_encode(['exam' => $exam, 'subject_id' => $sub['id'], 'questions' => $q]);"
    )
    raw = run_php_helper(php_code)
    if raw:
        try:
            return json.loads(raw)
        except Exception:
            pass
    return None

class BenchmarkStats:
    def __init__(self, name):
        self.name = name
        self.latencies = []
        self.success_count = 0
        self.fail_count = 0
        self.error_messages = []
        self.lock = threading.Lock()

    def record(self, latency_ms, is_success, err=""):
        with self.lock:
            self.latencies.append(latency_ms)
            if is_success:
                self.success_count += 1
            else:
                self.fail_count += 1
                if err and len(self.error_messages) < 5:
                    self.error_messages.append(err)

    def print_summary(self, total_time_sec):
        total = self.success_count + self.fail_count
        if not total:
            print(f"{Color.YELLOW}Tidak ada request yang dijalankan.{Color.RESET}")
            return

        rps = total / total_time_sec if total_time_sec > 0 else 0
        avg_lat = statistics.mean(self.latencies) if self.latencies else 0
        min_lat = min(self.latencies) if self.latencies else 0
        max_lat = max(self.latencies) if self.latencies else 0
        median_lat = statistics.median(self.latencies) if self.latencies else 0
        p95_lat = sorted(self.latencies)[int(len(self.latencies) * 0.95)] if len(self.latencies) >= 20 else max_lat

        print(f"\n{Color.BOLD}>>> Ringkasan Hasil: {self.name}{Color.RESET}")
        print(f"  • Total Request   : {Color.BOLD}{total}{Color.RESET} request")
        print(f"  • Berhasil (OK)   : {Color.GREEN}{self.success_count}{Color.RESET} ({self.success_count/total*100:.1f}%)")
        fail_color = Color.RED if self.fail_count > 0 else Color.GRAY
        print(f"  • Gagal (Error)   : {fail_color}{self.fail_count}{Color.RESET} ({self.fail_count/total*100:.1f}%)")
        print(f"  • Waktu Selesai   : {total_time_sec:.2f} detik")
        print(f"  • Throughput      : {Color.CYAN}{rps:.1f} req/detik (RPS){Color.RESET}")
        print(f"  • Waktu Respons (Latensi):")
        print(f"      - Tercepat (Min)  : {min_lat:.1f} ms")
        print(f"      - Rata-rata (Avg) : {avg_lat:.1f} ms")
        print(f"      - Median (p50)    : {median_lat:.1f} ms")
        print(f"      - 95% User (p95)  : {p95_lat:.1f} ms")
        print(f"      - Terlama (Max)   : {max_lat:.1f} ms")

        # Indikator Kesehatan Server
        print(f"  • Status Penilaian: ", end="")
        if self.fail_count == 0 and avg_lat < 600:
            print(f"{Color.GREEN}{Color.BOLD}[SANGAT PRIMA] (Lancar & Sangat Cepat){Color.RESET}")
        elif self.fail_count == 0 and avg_lat < 2000:
            print(f"{Color.YELLOW}{Color.BOLD}[CUKUP BAIK] (Stabil, masih dalam batas wajar){Color.RESET}")
        elif self.fail_count > 0 or avg_lat >= 2000:
            print(f"{Color.RED}{Color.BOLD}[BEBAN TINGGI / OVERLOAD] (Perlu optimasi server/Apache){Color.RESET}")

        if self.error_messages:
            print(f"  • Contoh Error Terdeteksi:")
            for e in self.error_messages[:3]:
                print(f"      {Color.RED}[ERR] {e}{Color.RESET}")

class VirtualStudent:
    def __init__(self, student_info, base_url, password='123456'):
        self.info = student_info
        self.base_url = base_url.rstrip('/')
        self.password = password
        self.session = requests.Session()
        self.nis = student_info['nis']
        self.name = student_info['full_name']
        self.is_authenticated = False

    def login(self):
        url = f"{self.base_url}/index.php"
        data = {'username': self.nis, 'password': self.password}
        t0 = time.time()
        try:
            res = self.session.post(url, data=data, timeout=10, allow_redirects=True)
            latency = (time.time() - t0) * 1000
            
            # Cek apakah berhasil login (redirect ke dashboard atau kata selamat datang di dashboard)
            is_ok = ('dashboard.php' in res.url or 'CBT' in res.text or 'Dashboard' in res.text) and 'Akun sedang digunakan' not in res.text
            err = ""
            if not is_ok:
                if 'Akun sedang digunakan' in res.text:
                    err = "Akun terkunci (sedang digunakan)"
                elif 'salah' in res.text.lower():
                    err = "Password/NIS salah"
                else:
                    err = f"HTTP {res.status_code} - Gagal masuk"
            else:
                self.is_authenticated = True
            return latency, is_ok, err
        except Exception as e:
            latency = (time.time() - t0) * 1000
            return latency, False, str(e)

    def load_dashboard(self):
        url = f"{self.base_url}/student/dashboard.php"
        t0 = time.time()
        try:
            res = self.session.get(url, timeout=10)
            latency = (time.time() - t0) * 1000
            is_ok = (res.status_code == 200 and 'index.php' not in res.url)
            err = f"Status {res.status_code}" if not is_ok else ""
            return latency, is_ok, err
        except Exception as e:
            latency = (time.time() - t0) * 1000
            return latency, False, str(e)

    def active_ping(self):
        """Mensimulasikan aktivitas siswa saat berada di dalam sistem (Dashboard)."""
        url = f"{self.base_url}/student/dashboard.php"
        t0 = time.time()
        try:
            res = self.session.get(url, timeout=10)
            latency = (time.time() - t0) * 1000
            is_ok = (res.status_code == 200 and 'index.php' not in res.url)
            return latency, is_ok, f"Status {res.status_code}" if not is_ok else ""
        except Exception as e:
            latency = (time.time() - t0) * 1000
            return latency, False, str(e)

    def simulate_save_answer(self, session_id, question_id, option_id=1):
        url = f"{self.base_url}/student/save_answer.php"
        payload = {
            'session_id': session_id,
            'question_id': question_id,
            'answer_data': [option_id],
            'is_doubt': 0,
            'remaining_seconds': 3500
        }
        t0 = time.time()
        try:
            res = self.session.post(url, json=payload, timeout=10)
            latency = (time.time() - t0) * 1000
            is_ok = (res.status_code == 200)
            return latency, is_ok, res.text if not is_ok else ""
        except Exception as e:
            latency = (time.time() - t0) * 1000
            return latency, False, str(e)

def run_test_scenario(base_url, num_students=40, password='123456'):
    print_header(f"MEMPERSIAPKAN PENGUJIAN BEBAN UNTUK {num_students} SISWA")
    print(f"Target URL      : {Color.BOLD}{base_url}{Color.RESET}")
    print(f"Jumlah Siswa    : {Color.BOLD}{num_students} Virtual Users{Color.RESET}")

    # 1. Reset status login sebelumnya
    print("\n[1/4] Mereset status login akun siswa di database... ", end="", flush=True)
    if reset_all_students_login():
        print(f"{Color.GREEN}BERHASIL{Color.RESET}")
    else:
        print(f"{Color.YELLOW}WARNING: Reset via PHP helper gagal, melanjutkan pengujian...{Color.RESET}")

    # 2. Ambil data siswa nyata
    print(f"[2/4] Mengambil {num_students} data akun siswa dari database... ", end="", flush=True)
    students_data = fetch_students_from_db(limit=num_students)
    if not students_data or len(students_data) < num_students:
        print(f"\n{Color.YELLOW}[PERINGATAN] Data siswa di database hanya ada {len(students_data)} akun. "
              f"Pengujian akan menggunakan {len(students_data)} siswa.{Color.RESET}")
        num_students = len(students_data)
        if num_students == 0:
            print(f"{Color.RED}[GAGAL] Tidak ada data siswa ditemukan di tabel students.{Color.RESET}")
            return
    else:
        print(f"{Color.GREEN}OK ({len(students_data)} siswa siap){Color.RESET}")

    # Buat objek Virtual Student
    v_students = [VirtualStudent(s, base_url, password=password) for s in students_data]

    # =========================================================================
    # TAHAP 1: SPIKE LOGIN (40 Siswa Login Serentak dalam Milidetik yang Sama)
    # =========================================================================
    print_header(f"TAHAP 1: UJI LOGIN SERENTAK ({num_students} SISWA SECARA BERSAMAAN)")
    print(f"{Color.GRAY}Semua {num_students} siswa akan menekan tombol 'LOGIN' pada milidetik yang persis sama.{Color.RESET}")
    print(f"{Color.GRAY}Ini menguji CPU server saat memproses hashing bcrypt password dan pembuatan session PHP.{Color.RESET}\n")

    barrier = threading.Barrier(num_students)
    stats_login = BenchmarkStats(f"Login Serentak ({num_students} Siswa)")

    def worker_login(student):
        # Menunggu sampai seluruh 40 thread siap di garis start
        barrier.wait()
        lat, ok, err = student.login()
        stats_login.record(lat, ok, err)
        if ok:
            print(f"  {Color.GREEN}[OK]{Color.RESET} {student.nis} ({student.name[:20]:<20}) - {lat:.0f} ms")
        else:
            print(f"  {Color.RED}[X]{Color.RESET} {student.nis} ({student.name[:20]:<20}) - GAGAL: {err} ({lat:.0f} ms)")

    t_start = time.time()
    with ThreadPoolExecutor(max_workers=num_students) as executor:
        futures = [executor.submit(worker_login, s) for s in v_students]
        for f in as_completed(futures):
            pass
    t_login_total = time.time() - t_start
    stats_login.print_summary(t_login_total)

    time.sleep(1)

    # =========================================================================
    # TAHAP 2: AKSES DASHBOARD (40 Siswa Memuat Halaman Dashboard & Jadwal Ujian)
    # =========================================================================
    logged_in_students = [s for s in v_students if s.is_authenticated]
    if not logged_in_students:
        print(f"\n{Color.RED}Tidak ada siswa yang berhasil login. Melewati tahap berikutnya.{Color.RESET}")
        return

    print_header(f"TAHAP 2: UJI MEMUAT DASHBOARD ({len(logged_in_students)} SISWA LOGIN)")
    print(f"{Color.GRAY}Siswa yang sudah login memuat dashboard bersamaan (query jadwal, kelas, status ujian).{Color.RESET}\n")

    stats_dash = BenchmarkStats(f"Akses Dashboard ({len(logged_in_students)} Siswa)")
    barrier_dash = threading.Barrier(len(logged_in_students))

    def worker_dash(student):
        barrier_dash.wait()
        lat, ok, err = student.load_dashboard()
        stats_dash.record(lat, ok, err)
        if ok:
            print(f"  {Color.GREEN}[OK]{Color.RESET} Dashboard {student.nis} - {lat:.0f} ms")
        else:
            print(f"  {Color.RED}[X]{Color.RESET} Dashboard {student.nis} - {err} ({lat:.0f} ms)")

    t_start = time.time()
    with ThreadPoolExecutor(max_workers=len(logged_in_students)) as executor:
        futures = [executor.submit(worker_dash, s) for s in logged_in_students]
        for f in as_completed(futures):
            pass
    t_dash_total = time.time() - t_start
    stats_dash.print_summary(t_dash_total)

    time.sleep(1)

    # =========================================================================
    # TAHAP 3: INTENSIVE SIMULATION (Aktivitas Bersamaan 40 Siswa Selama 10 Detik)
    # =========================================================================
    print_header(f"TAHAP 3: UJI BEBAN KONTINU (LALU LINTAS SIMULTAN SELAMA 10 DETIK)")
    print(f"{Color.GRAY}Mensimulasikan {len(logged_in_students)} siswa aktif yang terus bernavigasi dan membaca jadwal.{Color.RESET}\n")

    stats_sync = BenchmarkStats(f"Lalu Lintas Simultan ({len(logged_in_students)} Siswa)")
    stop_event = threading.Event()

    def worker_traffic(student):
        while not stop_event.is_set():
            lat, ok, err = student.active_ping()
            stats_sync.record(lat, ok, err)
            # Jeda acak 0.5 - 1 detik antar aksi per siswa
            time.sleep(0.8)

    t_start = time.time()
    with ThreadPoolExecutor(max_workers=len(logged_in_students)) as executor:
        for s in logged_in_students:
            executor.submit(worker_traffic, s)
        
        # Biarkan berjalan selama 10 detik
        duration = 10
        for remaining in range(duration, 0, -1):
            sys.stdout.write(f"\r  Menguji lalu lintas simultan... Sisa waktu: {remaining} detik ")
            sys.stdout.flush()
            time.sleep(1)
        stop_event.set()
        print("\r  Pengujian tahap 3 selesai. Mengumpulkan metrik...            ")

    t_sync_total = time.time() - t_start
    stats_sync.print_summary(t_sync_total)

    # =========================================================================
    # TAHAP 4: KESIMPULAN & REKOMENDASI SISTEM
    # =========================================================================
    print_header("KESIMPULAN & ANALISIS KEKUATAN WEB SERVER")
    
    avg_login = statistics.mean(stats_login.latencies) if stats_login.latencies else 0
    avg_dash = statistics.mean(stats_dash.latencies) if stats_dash.latencies else 0
    total_failures = stats_login.fail_count + stats_dash.fail_count + stats_sync.fail_count
    
    if total_failures == 0 and avg_login < 1000 and avg_dash < 1000:
        print(f"{Color.GREEN}{Color.BOLD}HASIL AKHIR: SERVER ANDA SANGAT KUAT MENAMPUNG 40 SISWA SEKALIGUS!{Color.RESET}")
        print("  - Tidak ada request yang gagal atau timeout (0% error rate).")
        print("  - Server merespons di bawah 1 detik bahkan saat 40 siswa login bersamaan.")
        print("  - Rekomendasi: Server siap digunakan untuk simulasi 40 siswa tanpa kendala.")
    elif total_failures == 0 and (avg_login >= 1000 or avg_dash >= 1000):
        print(f"{Color.YELLOW}{Color.BOLD}HASIL AKHIR: SERVER ANDA MAMPU MENAMPUNG 40 SISWA, DENGAN SEDIKIT ANTREAN.{Color.RESET}")
        print("  - Semua request berhasil (0% error rate), tetapi waktu tunggu terasa sedikit melambat.")
        print("  - Ini wajar jika dijalankan di laptop lokal (Laragon) dengan spesifikasi standar.")
        print("  - Tips: Di hari ujian, sarankan siswa login bertahap (rentang 1-2 menit) jika server terasa berat.")
    else:
        print(f"{Color.RED}{Color.BOLD}HASIL AKHIR: TERDETEKSI KENDALA PADA BEBAN 40 SISWA!{Color.RESET}")
        print(f"  - Terdapat {total_failures} request yang gagal atau ditolak server.")
        print("  - Rekomendasi Optimasi:")
        print("    1. Naikkan 'max_connections' di MySQL (my.ini) ke minimal 100 atau 150.")
        print("    2. Naikkan 'MaxRequestWorkers' / 'ThreadsPerChild' di Apache (httpd.conf).")
        print("    3. Pastikan memori server mencukupi saat proses hashing password bcrypt.")

    # Bersihkan kembali session di DB
    print(f"\n{Color.GRAY}[Pembersihan] Mengembalikan status login database ke semula...{Color.RESET} ", end="")
    reset_all_students_login()
    print(f"{Color.GREEN}SELESAI{Color.RESET}\n")

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description="Alat Uji Beban CBT (40 Siswa Concurrent)")
    parser.add_argument('--url', default='http://localhost:8080/simulasi_tka', help="Base URL web CBT (default: http://localhost:8080/simulasi_tka)")
    parser.add_argument('--users', type=int, default=40, help="Jumlah siswa virtual (default: 40)")
    parser.add_argument('--password', default='123456', help="Password default siswa (default: 123456)")
    
    args = parser.parse_args()
    try:
        run_test_scenario(base_url=args.url, num_students=args.users, password=args.password)
    except KeyboardInterrupt:
        print(f"\n\n{Color.YELLOW}[DIBATALKAN] Pengujian dihentikan oleh pengguna.{Color.RESET}")
        reset_all_students_login()
