<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    /**
     * Mengambil riwayat percakapan pengguna
     * GET /api/chatbot/history
     */
    public function getHistory(Request $request)
    {
        $user = $request->user();
        $history = $user->chatMessages()
            ->orderBy('id', 'asc')
            ->get(['id', 'message', 'is_user', 'function_called', 'created_at']);

        return response()->json([
            'success' => true,
            'data'    => $history,
        ], 200);
    }

    /**
     * Menghapus seluruh riwayat percakapan pengguna
     * DELETE /api/chatbot/history
     */
    public function clearHistory(Request $request)
    {
        $user = $request->user();
        $user->chatMessages()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat percakapan berhasil dibersihkan.',
        ], 200);
    }

    /**
     * Endpoint utama untuk berinteraksi dengan AI Financial Chatbot
     * POST /api/chatbot/ask atau /api/chat
     */
    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array',
        ]);

        $user = $request->user();
        $userMessage = $request->input('message');
        $chatHistory = $request->input('history', []);

        // 1. SEBELUM memanggil Gemini: Simpan pesan pengguna ke tabel chat_messages
        ChatMessage::create([
            'user_id' => $user->id,
            'message' => $userMessage,
            'is_user' => true,
        ]);

        $apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));
        $model = config('services.gemini.model', env('GEMINI_MODEL', 'gemini-2.5-flash'));

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Gemini API Key belum dikonfigurasi di server. Mohon tambahkan GEMINI_API_KEY di file .env',
            ], 500);
        }

        $now = Carbon::now();
        $currentMonthName = $now->translatedFormat('F');
        $currentYear = $now->year;
        $currentMonth = $now->month;

        // System Instruction Persona & Rules
        $systemInstruction = [
            'parts' => [
                [
                    'text' => "Anda adalah 'FinTrack AI', asisten penasihat keuangan pribadi yang cerdas, ramah, profesional, dan empatik. " .
                        "Waktu saat ini adalah tanggal {$now->translatedFormat('d F Y')}. Bulan saat ini adalah bulan ke-{$currentMonth} ({$currentMonthName} {$currentYear}). " .
                        "Tugas utama Anda adalah membantu pengguna menganalisis keuangan, menjawab pertanyaan terkait transaksi, pengeluaran, pemasukan, dan memberikan tips hemat. " .
                        "ATURAN MUTLAK:\n" .
                        "1. Jika pengguna menanyakan data keuangan mereka (seperti total pengeluaran, pengeluaran kategori, item terboros, atau sisa budget), Anda WAJIB memanggil tools (Function Calling) yang relevan untuk mengambil data akurat.\n" .
                        "2. Jawab selalu dalam Bahasa Indonesia dengan nada yang hangat, jelas, dan rapi (gunakan format nominal Rupiah seperti Rp 150.000).\n" .
                        "3. Berikan saran keuangan praktis yang membangun jika pengeluaran pengguna tampak tinggi atau melebihi anggaran."
                ]
            ]
        ];

        // Definisi Function Declarations (Tools) untuk Gemini
        $tools = [
            [
                'function_declarations' => [
                    [
                        'name' => 'get_total_expense',
                        'description' => 'Mengambil total nominal pengeluaran pengguna pada bulan dan tahun tertentu.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'month' => ['type' => 'INTEGER', 'description' => "Nomor bulan (1-12). Default ke {$currentMonth} jika tidak disebutkan."],
                                'year'  => ['type' => 'INTEGER', 'description' => "Tahun 4 digit (misal {$currentYear}). Default ke {$currentYear} jika tidak disebutkan."],
                            ],
                        ],
                    ],
                    [
                        'name' => 'get_total_income',
                        'description' => 'Mengambil total nominal pemasukan pengguna pada bulan dan tahun tertentu.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'month' => ['type' => 'INTEGER', 'description' => "Nomor bulan (1-12). Default ke {$currentMonth}."],
                                'year'  => ['type' => 'INTEGER', 'description' => "Tahun 4 digit. Default ke {$currentYear}."],
                            ],
                        ],
                    ],
                    [
                        'name' => 'get_expense_by_category',
                        'description' => 'Mengambil total pengeluaran dan jumlah transaksi pada kategori tertentu (contoh: Makanan, Transportasi, Belanja, Tagihan) pada bulan dan tahun tertentu.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'category_name' => ['type' => 'STRING', 'description' => 'Nama kategori yang ingin dicek (misal: Makan, Bensin, Belanja, Kopi).'],
                                'month'         => ['type' => 'INTEGER', 'description' => 'Nomor bulan (1-12).'],
                                'year'          => ['type' => 'INTEGER', 'description' => 'Tahun 4 digit.'],
                            ],
                            'required' => ['category_name'],
                        ],
                    ],
                    [
                        'name' => 'get_highest_expense_item',
                        'description' => 'Mengambil daftar transaksi pengeluaran terbesar / item paling menguras dana pengguna pada bulan dan tahun tertentu.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'month' => ['type' => 'INTEGER', 'description' => 'Nomor bulan (1-12).'],
                                'year'  => ['type' => 'INTEGER', 'description' => 'Tahun 4 digit.'],
                                'limit' => ['type' => 'INTEGER', 'description' => 'Jumlah item teratas yang diambil (default: 3).'],
                            ],
                        ],
                    ],
                    [
                        'name' => 'get_budget_status',
                        'description' => 'Mengambil status limit anggaran (budget), pengeluaran aktual per kategori budget, dan sisa limit anggaran pengguna.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'month' => ['type' => 'INTEGER', 'description' => 'Nomor bulan (1-12).'],
                                'year'  => ['type' => 'INTEGER', 'description' => 'Tahun 4 digit.'],
                            ],
                        ],
                    ],
                ]
            ]
        ];

        // Format history dan pesan pengguna saat ini
        $contents = [];
        if (!empty($chatHistory)) {
            foreach ($chatHistory as $msg) {
                $role = ($msg['role'] ?? 'user') === 'user' ? 'user' : 'model';
                $contents[] = [
                    'role' => $role,
                    'parts' => [
                        ['text' => $msg['text'] ?? $msg['content'] ?? '']
                    ]
                ];
            }
        }

        // Tambahkan pertanyaan pengguna saat ini
        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $userMessage]
            ]
        ];

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        try {
            // Request Tahap 1: Kirim pesan pengguna & tools ke Gemini
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(30)
                ->post($endpoint, [
                    'system_instruction' => $systemInstruction,
                    'tools'              => $tools,
                    'contents'           => $contents,
                ]);

            if ($response->failed()) {
                Log::error('Gemini API initial call failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghubungi layanan AI Gemini: ' . ($response->json('error.message') ?? 'Unknown error'),
                ], 502);
            }

            $result = $response->json();
            $candidate = $result['candidates'][0]['content'] ?? null;

            if (!$candidate) {
                return response()->json([
                    'success' => true,
                    'reply'   => 'Maaf, saya tidak dapat memahami permintaan tersebut. Ada yang bisa saya bantu terkait transaksi Anda?',
                ]);
            }

            // Periksa apakah Gemini meminta eksekusi Function Call (Tools)
            $functionCallPart = null;
            foreach ($candidate['parts'] as $part) {
                if (isset($part['functionCall'])) {
                    $functionCallPart = $part['functionCall'];
                    break;
                }
            }

            // Jika Gemini TIDAK memanggil function (hanya chat percakapan biasa)
            if (!$functionCallPart) {
                $finalText = $candidate['parts'][0]['text'] ?? '';

                // 2a. Simpan balasan AI (tanpa function call) ke database
                ChatMessage::create([
                    'user_id' => $user->id,
                    'message' => $finalText,
                    'is_user' => false,
                ]);

                return response()->json([
                    'success' => true,
                    'reply'   => $finalText,
                ]);
            }

            // -----------------------------------------------------------------
            // EKSEKUSI FUNCTION CALL DENGAN MULTI-TENANT SECURITY TERJAMIN
            // Parameter user_id di-inject langsung di PHP menggunakan auth()->id()
            // -----------------------------------------------------------------
            $functionName = $functionCallPart['name'];
            $functionArgs = $functionCallPart['args'] ?? [];

            $toolExecutionResult = $this->executeTool($functionName, $functionArgs, $user);

            // Masukkan respons model (yang memuat functionCall) ke dalam riwayat percakapan
            $contents[] = $candidate;

            // Masukkan respons hasil eksekusi tool ke dalam riwayat percakapan
            $contents[] = [
                'role' => 'function',
                'parts' => [
                    [
                        'functionResponse' => [
                            'name' => $functionName,
                            'response' => [
                                'name'    => $functionName,
                                'content' => $toolExecutionResult,
                            ]
                        ]
                    ]
                ]
            ];

            // Request Tahap 2: Kirim kembali hasil tool ke Gemini untuk menghasilkan teks akhir
            $secondResponse = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(30)
                ->post($endpoint, [
                    'system_instruction' => $systemInstruction,
                    'tools'              => $tools,
                    'contents'           => $contents,
                ]);

            if ($secondResponse->failed()) {
                Log::error('Gemini API second call failed', [
                    'status' => $secondResponse->status(),
                    'body'   => $secondResponse->body(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memproses data jawaban dari AI.',
                ], 502);
            }

            $secondResult = $secondResponse->json();
            $finalCandidate = $secondResult['candidates'][0]['content'] ?? null;
            $finalReply = $finalCandidate['parts'][0]['text'] ?? 'Data berhasil ditemukan.';

            // 2b. Simpan balasan AI (hasil function call) ke database
            ChatMessage::create([
                'user_id'         => $user->id,
                'message'         => $finalReply,
                'is_user'         => false,
                'function_called' => $functionName,
            ]);

            return response()->json([
                'success'         => true,
                'reply'           => $finalReply,
                'function_called' => $functionName,
                'raw_tool_data'   => $toolExecutionResult,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Chatbot Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses pesan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eksekutor Tool Function Calling (KEAMANAN MULTI-TENANT MUTLAK)
     * Seluruh query WAJIB difilter berdasarkan user yang sedang login ($user->id).
     */
    private function executeTool(string $functionName, array $args, $user): array
    {
        $now = Carbon::now();
        $month = isset($args['month']) && (int)$args['month'] >= 1 && (int)$args['month'] <= 12
            ? (int)$args['month']
            : (int)$now->month;

        $year = isset($args['year']) && (int)$args['year'] >= 1000 && (int)$args['year'] <= 9999
            ? (int)$args['year']
            : (int)$now->year;

        $periodLabel = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');

        switch ($functionName) {
            case 'get_total_expense':
                // Query mutlak user_id
                $totalExpense = (float) $user->transactions()
                    ->where('type', 'expense')
                    ->whereMonth('date', $month)
                    ->whereYear('date', $year)
                    ->sum(DB::raw('amount + COALESCE(admin_fee, 0)'));

                $count = $user->transactions()
                    ->where('type', 'expense')
                    ->whereMonth('date', $month)
                    ->whereYear('date', $year)
                    ->count();

                return [
                    'period'            => $periodLabel,
                    'total_expense'     => $totalExpense,
                    'formatted_expense' => 'Rp ' . number_format($totalExpense, 0, ',', '.'),
                    'transaction_count' => $count,
                ];

            case 'get_total_income':
                $totalIncome = (float) $user->transactions()
                    ->where('type', 'income')
                    ->whereMonth('date', $month)
                    ->whereYear('date', $year)
                    ->sum(DB::raw('amount + COALESCE(admin_fee, 0)'));

                $count = $user->transactions()
                    ->where('type', 'income')
                    ->whereMonth('date', $month)
                    ->whereYear('date', $year)
                    ->count();

                return [
                    'period'           => $periodLabel,
                    'total_income'     => $totalIncome,
                    'formatted_income' => 'Rp ' . number_format($totalIncome, 0, ',', '.'),
                    'transaction_count'=> $count,
                ];

            case 'get_expense_by_category':
                $categoryName = trim($args['category_name'] ?? '');

                $query = $user->transactions()
                    ->with('category')
                    ->where('type', 'expense')
                    ->whereMonth('date', $month)
                    ->whereYear('date', $year);

                if (!empty($categoryName)) {
                    $query->whereHas('category', function ($q) use ($categoryName) {
                        $q->where('name', 'LIKE', "%{$categoryName}%");
                    });
                }

                $transactions = $query->get();
                $total = (float) $transactions->sum(function ($tx) {
                    return (float) ($tx->amount + ($tx->admin_fee ?? 0));
                });

                return [
                    'category'          => $categoryName ?: 'Semua Kategori',
                    'period'            => $periodLabel,
                    'total_expense'     => $total,
                    'formatted_expense' => 'Rp ' . number_format($total, 0, ',', '.'),
                    'transaction_count' => $transactions->count(),
                ];

            case 'get_highest_expense_item':
                $limit = isset($args['limit']) ? max(1, min(10, (int)$args['limit'])) : 3;

                $items = $user->transactions()
                    ->with('category:id,name')
                    ->where('type', 'expense')
                    ->whereMonth('date', $month)
                    ->whereYear('date', $year)
                    ->orderByDesc(DB::raw('amount + COALESCE(admin_fee, 0)'))
                    ->take($limit)
                    ->get()
                    ->map(function ($tx) {
                        $total = (float)($tx->amount + ($tx->admin_fee ?? 0));
                        return [
                            'title'     => $tx->title,
                            'category'  => $tx->category ? $tx->category->name : 'Lainnya',
                            'total'     => $total,
                            'formatted' => 'Rp ' . number_format($total, 0, ',', '.'),
                            'date'      => Carbon::parse($tx->date)->translatedFormat('d M Y'),
                        ];
                    });

                return [
                    'period'           => $periodLabel,
                    'highest_expenses' => $items->values()->all(),
                ];

            case 'get_budget_status':
                $monthYearStr = sprintf('%02d-%04d', $month, $year);

                $budgets = $user->budgets()
                    ->with('category:id,name')
                    ->where('month_year', $monthYearStr)
                    ->get();

                if ($budgets->isEmpty()) {
                    $budgets = $user->budgets()
                        ->with('category:id,name')
                        ->get()
                        ->groupBy('category_id')
                        ->map(fn($g) => $g->first())
                        ->values();
                }

                $budgetList = $budgets->map(function ($b) use ($user, $month, $year) {
                    $spent = (float) $user->transactions()
                        ->where('type', 'expense')
                        ->where('category_id', $b->category_id)
                        ->whereMonth('date', $month)
                        ->whereYear('date', $year)
                        ->sum(DB::raw('amount + COALESCE(admin_fee, 0)'));

                    $limit = (float) $b->limit_amount;
                    $remaining = $limit - $spent;

                    return [
                        'category'            => $b->category ? $b->category->name : 'Kategori',
                        'limit_amount'        => $limit,
                        'spent_amount'        => $spent,
                        'remaining_amount'    => $remaining,
                        'formatted_limit'     => 'Rp ' . number_format($limit, 0, ',', '.'),
                        'formatted_spent'     => 'Rp ' . number_format($spent, 0, ',', '.'),
                        'formatted_remaining' => 'Rp ' . number_format($remaining, 0, ',', '.'),
                        'is_exceeded'         => $spent > $limit,
                    ];
                });

                return [
                    'period'       => $periodLabel,
                    'budget_items' => $budgetList->values()->all(),
                ];

            default:
                return [
                    'status'  => 'unknown_function',
                    'message' => "Function $functionName tidak dikenali.",
                ];
        }
    }
}

