<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Izin;
use App\Models\Pegawai;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TelegramWebhookController extends Controller
{
    protected TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Handle incoming telegram webhook updates
     */
    public function handle(Request $request)
    {
        $update = $request->all();

        // Basic check if it's a message
        if (!isset($update['message'])) {
            return response()->json(['status' => 'ok']);
        }

        $message = $update['message'];
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        
        // SECURITY: Match with TELEGRAM_CHAT_ID if you want to restrict to 1 group
        $allowedChatId = config('services.telegram.chat_id');
        if (!empty($allowedChatId) && $chatId != $allowedChatId) {
            // Log::info('Unauthorized Telegram Chat ID', ['id' => $chatId]);
            return response()->json(['status' => 'ok']);
        }

        Log::info('Telegram Webhook Update', ['text' => $text, 'chat_id' => $chatId]);

        // Process Command
        $cleanText = strtolower(explode('@', $text)[0]);

        switch ($cleanText) {
            case '/start':
            case '/menu':
            case 'menu':
                $this->sendMenu($chatId);
                break;
            
            case '/summary':
            case '📊 summary hari ini':
                $this->sendSummary($chatId);
                break;
                
            case '/terlambat':
            case '⏰ terlambat hari ini':
                $this->sendTerlambat($chatId);
                break;

            case '✅ sudah absen':
                $this->sendSudahAbsen($chatId);
                break;

            case '⏳ belum absen':
                $this->sendBelumAbsen($chatId);
                break;

            case '📝 izin pending':
                $this->sendIzinPending($chatId);
                break;

            case '🔗 link dashboard':
                $this->telegram->sendMessage("<b>Dashboard Admin:</b>\n" . config('app.url') . "/admin", 'HTML', $chatId);
                break;
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Display main menu as plain text
     */
    protected function sendMenu($chatId)
    {
        $msg = "<b>📋 MENU BOT ABSENSI</b>\n";
        $msg .= "----------------------------\n";
        $msg .= "Silakan ketik perintah di bawah ini:\n\n";
        $msg .= "1. 📊 <b>Summary</b> - Laporan ringkasan hari ini\n";
        $msg .= "2. ⏰ <b>Terlambat</b> - Daftar pegawai terlambat\n";
        $msg .= "3. ✅ <b>Sudah Absen</b> - Daftar pegawai masuk\n";
        $msg .= "4. ⏳ <b>Belum Absen</b> - Daftar pegawai belum masuk\n";
        $msg .= "5. 📝 <b>Izin Pending</b> - Laporan izin tertunda\n";
        $msg .= "6. 🔗 <b>Link Dashboard</b> - Link web admin\n";
        $msg .= "----------------------------\n";
        $msg .= "<i>Ketik 'menu' kapan saja untuk melihat daftar ini.</i>";

        $this->telegram->sendMessage($msg, 'HTML', $chatId);
    }

    /**
     * Send list of late employees today
     */
    protected function sendTerlambat($chatId)
    {
        $absensis = Absensi::with('pegawai')
            ->whereDate('tanggal', Carbon::today())
            ->where('status', 'Terlambat')
            ->get();

        if ($absensis->isEmpty()) {
            $this->telegram->sendMessage("✅ Tidak ada pegawai yang terlambat hari ini.", 'HTML', $chatId);
            return;
        }

        $msg = "<b>⏰ DAFTAR TERLAMBAT HARI INI</b>\n\n";
        foreach ($absensis as $index => $absen) {
            $nama = $absen->pegawai->nama_lengkap ?? $absen->pegawai->nama;
            $jam = Carbon::parse($absen->jam_masuk)->format('H:i');
            $msg .= ($index + 1) . ". 🔴 {$nama} (Masuk: {$jam})\n";
        }

        $this->telegram->sendMessage($msg, 'HTML', $chatId);
    }

    /**
     * Send list of people who checked in today
     */
    protected function sendSudahAbsen($chatId)
    {
        $absensis = Absensi::with('pegawai')
            ->whereDate('tanggal', Carbon::today())
            ->orderBy('jam_masuk', 'asc')
            ->get();

        if ($absensis->isEmpty()) {
            $this->telegram->sendMessage("Belum ada pegawai yang absen hari ini.", 'HTML', $chatId);
            return;
        }

        $msg = "<b>✅ PEGAWAI SUDAH MASUK</b>\n\n";
        foreach ($absensis as $index => $absen) {
            $nama = $absen->pegawai->nama_lengkap ?? $absen->pegawai->nama ?? 'Unknown';
            $jam = Carbon::parse($absen->jam_masuk)->format('H:i');
            $status = $absen->status;
            
            $icon = $status == 'Tepat Waktu' ? '🟢' : '🟡';
            $msg .= ($index + 1) . ". {$icon} {$nama} ({$jam})\n";
        }

        $this->telegram->sendMessage($msg, 'HTML', $chatId);
    }

    /**
     * Send list of people who haven't checked in today
     */
    protected function sendBelumAbsen($chatId)
    {
        $today = Carbon::today();
        
        // Pegawai yang sudah absen
        $sudahMasukIds = Absensi::whereDate('tanggal', $today)->pluck('pegawai_id')->toArray();
        
        // Pegawai yang sedang izin
        $izinIds = Izin::whereDate('tgl_mulai', '<=', $today)
                        ->whereDate('tgl_selesai', '>=', $today)
                        ->where('status_approval', 'Approved')
                        ->pluck('pegawai_id')
                        ->toArray();
        
        $excludeIds = array_merge($sudahMasukIds, $izinIds);
        
        $belumAbsens = Pegawai::whereNotIn('id', $excludeIds)->get();

        if ($belumAbsens->isEmpty()) {
            $this->telegram->sendMessage("Luar biasa! Semua pegawai sudah absen atau izin.", 'HTML', $chatId);
            return;
        }

        $msg = "<b>⏳ PEGAWAI BELUM ABSEN</b>\n\n";
        foreach ($belumAbsens as $index => $p) {
            $nama = $p->nama_lengkap ?? $p->nama;
            $msg .= ($index + 1) . ". ⚪️ {$nama}\n";
        }

        $this->telegram->sendMessage($msg, 'HTML', $chatId);
    }

    /**
     * Send list of pending leave requests
     */
    protected function sendIzinPending($chatId)
    {
        $izins = Izin::with(['pegawai', 'jenisIzin'])
            ->where('status_approval', 'Pending')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($izins->isEmpty()) {
            $this->telegram->sendMessage("Tidak ada pengajuan izin yang tertunda.", 'HTML', $chatId);
            return;
        }

        $msg = "<b>📝 IZIN MENUNGGU PERSETUJUAN</b>\n\n";
        foreach ($izins as $index => $izin) {
            $nama = $izin->pegawai->nama_lengkap ?? $izin->pegawai->nama;
            $jenis = $izin->jenisIzin->nama ?? 'Izin';
            $tgl = $izin->tgl_mulai->format('d/m/Y');
            
            $msg .= ($index + 1) . ". 👤 {$nama}\n";
            $msg .= "   🔹 Jenis: {$jenis}\n";
            $msg .= "   📅 Tanggal: {$tgl}\n\n";
        }

        $this->telegram->sendMessage($msg, 'HTML', $chatId);
    }
}
