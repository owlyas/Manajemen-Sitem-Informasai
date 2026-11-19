<?php
/**
 * Bloom&Books - Floating Chatbot Component (BloomBot)
 * Komponen asisten belanja interaktif dengan glassmorphism UI, respons cerdas,
 * cek stok real-time, rekomendasi buku, dan riwayat pesanan.
 */

// Tentukan path API relatif berdasarkan lokasi halaman
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
$isSubdir = (strpos($currentScript, '/user/') !== false || strpos($currentScript, '/admin/') !== false);
$chatbotApiUrl = $isSubdir ? '../chatbot_api.php' : 'chatbot_api.php';
$baseIndexUrl = $isSubdir ? '../index.php' : 'index.php';
$cartActionUrl = $isSubdir ? '../cart.php' : 'cart.php';
?>

<!-- ========================================================
     BLOOMBOT FLOATING CHATBOT WIDGET
     ======================================================== -->
<div id="bloomChatbotContainer" class="fixed bottom-6 right-6 z-50 font-sans print:hidden select-none">

    <!-- 1. FLOATING CHAT TOGGLE BUTTON -->
    <div class="relative group">
        <!-- Floating Prompt Tooltip -->
        <div id="chatbotTooltip" class="absolute right-0 bottom-16 mb-2 hidden md:flex items-center gap-2.5 px-3.5 py-2 rounded-2xl bg-white/95 backdrop-blur-md border border-purple-100 shadow-xl shadow-purple-900/10 text-xs font-semibold text-slate-700 whitespace-nowrap animate-bounce transition-all duration-300 pointer-events-none">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
            <span>Butuh rekomendasi atau cek stok? Chat <strong>BloomBot</strong>!</span>
            <span class="text-secondary text-sm">✨</span>
        </div>

        <!-- The Main Floating Action Button (FAB) -->
        <button id="chatbotToggleBtn" type="button" aria-label="Buka Chatbot Asisten BloomBot"
            class="relative w-14 h-14 rounded-full bg-gradient-to-tr from-[#3A0CA3] via-[#7209B7] to-[#F72585] p-[1.5px] shadow-xl shadow-primary/30 hover:shadow-2xl hover:shadow-secondary/40 transition-all duration-300 hover:scale-105 active:scale-95 flex items-center justify-center focus:outline-none focus:ring-4 focus:ring-primary/20">
            <div class="w-full h-full rounded-full bg-gradient-to-br from-[#1E085A] to-[#3A0CA3] flex items-center justify-center relative overflow-hidden">
                <!-- Ambient glow inside button -->
                <div class="absolute -top-4 -right-4 w-10 h-10 bg-secondary/30 rounded-full blur-md"></div>
                
                <!-- Chat Icon (Default Visible) -->
                <svg id="chatbotIconChat" class="w-7 h-7 text-white transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    <path d="M8 10h.01M12 10h.01M16 10h.01" stroke-width="3"></path>
                </svg>

                <!-- Close / Cross Icon (Hidden by default) -->
                <svg id="chatbotIconClose" class="w-6 h-6 text-white hidden transition-transform duration-300 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>

            <!-- Pulsing Online Badge -->
            <span class="absolute top-0 right-0 flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500 border-2 border-white"></span>
            </span>
        </button>
    </div>

    <!-- 2. FLOATING CHAT WINDOW -->
    <div id="chatbotWindow" 
        class="hidden fixed bottom-24 right-4 sm:right-6 w-[calc(100vw-32px)] sm:w-[400px] h-[580px] max-h-[calc(100vh-120px)] bg-white/95 backdrop-blur-2xl rounded-3xl shadow-2xl shadow-primary/20 border border-white/80 flex flex-col overflow-hidden transition-all duration-300 origin-bottom-right z-50">
        
        <!-- Window Header -->
        <div class="p-4 bg-gradient-to-r from-[#1E085A] via-[#3A0CA3] to-[#7209B7] text-white flex items-center justify-between shadow-md relative overflow-hidden shrink-0">
            <!-- Background Decorative Blobs -->
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-secondary/30 rounded-full blur-xl pointer-events-none"></div>

            <div class="flex items-center gap-3 relative z-10">
                <!-- Bot Avatar Emblem -->
                <div class="relative w-10 h-10 rounded-2xl bg-gradient-to-tr from-secondary to-primary p-[1px] shadow-sm">
                    <div class="w-full h-full rounded-[14px] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-5 h-5 text-pink-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2 2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"></path>
                            <rect x="4" y="8" width="16" height="12" rx="3"></rect>
                            <path d="M9 13v2M15 13v2"></path>
                        </svg>
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-emerald-500 border-2 border-[#1E085A] rounded-full"></span>
                </div>

                <div>
                    <div class="flex items-center gap-1.5">
                        <h3 class="font-bold text-sm text-white tracking-tight">BloomBot</h3>
                        <span class="px-1.5 py-0.5 rounded-full text-[9px] font-extrabold bg-secondary/80 text-white tracking-wide uppercase">AI</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-[11px] text-purple-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Asisten Toko & Rekomendasi</span>
                    </div>
                </div>
            </div>

            <!-- Header Action Buttons -->
            <div class="flex items-center gap-1 relative z-10">
                <!-- Clear Chat Button -->
                <button id="chatbotClearBtn" type="button" title="Mulai Percakapan Baru"
                    class="p-2 rounded-xl text-purple-200 hover:text-white hover:bg-white/10 transition-colors focus:outline-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>

                <!-- Close / Minimize Button -->
                <button id="chatbotCloseBtn" type="button" title="Tutup Chat"
                    class="p-2 rounded-xl text-purple-200 hover:text-white hover:bg-white/10 transition-colors focus:outline-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Chat Messages Scrollable Body -->
        <div id="chatbotMessages" class="flex-1 p-4 overflow-y-auto space-y-4 bg-slate-50/70 scroll-smooth text-xs">
            <!-- Messages will be dynamically rendered here by JavaScript -->
        </div>

        <!-- Typing Indicator (Hidden by default) -->
        <div id="chatbotTyping" class="hidden px-4 py-2 bg-slate-50/70 shrink-0">
            <div class="flex items-center gap-2 text-slate-400 text-xs">
                <div class="w-6 h-6 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-bold text-[10px]">
                    BB
                </div>
                <div class="px-3 py-2 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary animate-bounce" style="animation-delay: 0ms"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-primary animate-bounce" style="animation-delay: 150ms"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-primary animate-bounce" style="animation-delay: 300ms"></span>
                </div>
                <span class="text-[11px] text-slate-400 font-medium italic">BloomBot sedang mengetik...</span>
            </div>
        </div>

        <!-- Quick Suggestion Action Chips -->
        <div id="chatbotSuggestionsContainer" class="px-3 py-2 bg-white/80 border-t border-slate-100 flex gap-1.5 overflow-x-auto no-scrollbar shrink-0">
            <!-- Chips rendered dynamically -->
        </div>

        <!-- Window Input Form -->
        <form id="chatbotForm" class="p-3 bg-white border-t border-slate-200/80 flex items-center gap-2 shrink-0">
            <input type="text" id="chatbotInput" 
                placeholder="Tanya stok, rekomendasi, dll..." 
                autocomplete="off"
                class="flex-1 py-2.5 px-3.5 rounded-2xl bg-slate-100/80 border border-slate-200 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/15 text-xs text-slate-800 placeholder-slate-400 outline-none transition-all">
            
            <button type="submit" id="chatbotSendBtn" aria-label="Kirim Pesan"
                class="w-10 h-10 rounded-2xl bg-gradient-to-r from-primary to-secondary text-white flex items-center justify-center shadow-md shadow-primary/20 hover:opacity-95 active:scale-95 transition-all shrink-0 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="w-4 h-4 transform rotate-45 -translate-y-0.5 -translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
            </button>
        </form>

    </div>
</div>

<style>
/* Chatbot subtle animations and custom scrollbars */
#chatbotMessages::-webkit-scrollbar,
#chatbotSuggestionsContainer::-webkit-scrollbar {
    width: 4px;
    height: 4px;
}
#chatbotMessages::-webkit-scrollbar-thumb,
#chatbotSuggestionsContainer::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.4);
    border-radius: 9999px;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
@keyframes chatSlideUp {
    from {
        opacity: 0;
        transform: translateY(16px) scale(0.96);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
.chat-window-open {
    animation: chatSlideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>

<script>
(function() {
    // URL konfigurasi dinamis berdasarkan lokasi file yang memanggil
    const CHATBOT_API_URL = '<?= $chatbotApiUrl ?>';
    const BASE_INDEX_URL = '<?= $baseIndexUrl ?>';
    const CART_ACTION_URL = '<?= $cartActionUrl ?>';

    // Elemen DOM Chatbot
    const toggleBtn = document.getElementById('chatbotToggleBtn');
    const chatbotWindow = document.getElementById('chatbotWindow');
    const closeBtn = document.getElementById('chatbotCloseBtn');
    const clearBtn = document.getElementById('chatbotClearBtn');
    const form = document.getElementById('chatbotForm');
    const input = document.getElementById('chatbotInput');
    const sendBtn = document.getElementById('chatbotSendBtn');
    const messagesContainer = document.getElementById('chatbotMessages');
    const typingIndicator = document.getElementById('chatbotTyping');
    const suggestionsContainer = document.getElementById('chatbotSuggestionsContainer');
    const tooltip = document.getElementById('chatbotTooltip');
    const iconChat = document.getElementById('chatbotIconChat');
    const iconClose = document.getElementById('chatbotIconClose');

    // Kunci LocalStorage untuk percakapan
    const STORAGE_KEY = 'bloom_chatbot_history_v1';
    let chatHistory = [];

    // Format waktu
    function formatTime(dateObj = new Date()) {
        return dateObj.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    }

    // Parse markdown sederhana (bold, link, bullet, newlines)
    function formatMarkdown(text) {
        if (!text) return '';
        let escaped = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        // Bold **teks**
        escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Italic *teks*
        escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');
        // Markdown Link [Teks](url)
        escaped = escaped.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-secondary font-bold hover:underline" target="_self">$1</a>');
        // Newlines
        escaped = escaped.replace(/\n/g, '<br>');

        return escaped;
    }

    // Toggle Tampilan Jendela Chatbot
    function toggleChatbot(open = null) {
        const isHidden = chatbotWindow.classList.contains('hidden');
        const shouldOpen = open !== null ? open : isHidden;

        if (shouldOpen) {
            chatbotWindow.classList.remove('hidden');
            chatbotWindow.classList.add('chat-window-open');
            iconChat.classList.add('hidden');
            iconClose.classList.remove('hidden');
            if (tooltip) tooltip.classList.add('hidden');
            
            // Auto focus input di desktop
            if (window.innerWidth > 640) {
                setTimeout(() => input.focus(), 200);
            }

            // Jika belum ada riwayat percakapan, inisialisasi dari API
            if (chatHistory.length === 0) {
                initChatbot();
            } else {
                scrollToBottom();
            }
        } else {
            chatbotWindow.classList.add('hidden');
            chatbotWindow.classList.remove('chat-window-open');
            iconChat.classList.remove('hidden');
            iconClose.classList.add('hidden');
        }
    }

    // Scroll ke bagian paling bawah pesan
    function scrollToBottom() {
        setTimeout(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 50);
    }

    // Tampilkan / Sembunyikan Indikator Mengetik
    function setTyping(loading) {
        if (loading) {
            typingIndicator.classList.remove('hidden');
            sendBtn.disabled = true;
            input.disabled = true;
        } else {
            typingIndicator.classList.add('hidden');
            sendBtn.disabled = false;
            input.disabled = false;
        }
        scrollToBottom();
    }

    // Render 1 bubble pesan ke dalam container
    function appendMessageBubble(msg) {
        const isBot = msg.sender === 'bot';
        const wrapper = document.createElement('div');
        wrapper.className = `flex gap-2.5 items-end ${isBot ? 'justify-start' : 'justify-end'}`;

        let html = '';

        if (isBot) {
            html += `
                <div class="w-7 h-7 rounded-2xl bg-gradient-to-tr from-[#3A0CA3] to-secondary p-[1px] shrink-0 mb-1 shadow-sm">
                    <div class="w-full h-full rounded-[14px] bg-white flex items-center justify-center text-primary font-bold text-[10px]">
                        BB
                    </div>
                </div>
            `;
        }

        html += `
            <div class="max-w-[85%] flex flex-col ${isBot ? 'items-start' : 'items-end'}">
                <div class="p-3 rounded-2xl text-xs leading-relaxed shadow-sm ${
                    isBot 
                    ? 'bg-white text-slate-800 rounded-bl-sm border border-slate-200/80 shadow-slate-200/50' 
                    : 'bg-gradient-to-r from-primary to-[#5A1FD6] text-white rounded-br-sm shadow-primary/20'
                }">
                    ${formatMarkdown(msg.text)}
                </div>
                <span class="text-[9px] text-slate-400 mt-1 px-1 font-medium">${msg.time || formatTime()}</span>
            </div>
        `;

        wrapper.innerHTML = html;
        messagesContainer.appendChild(wrapper);

        // Jika pesan bot memiliki daftar buku, render kartu buku interaktif
        if (isBot && msg.books && msg.books.length > 0) {
            renderBookCards(msg.books);
        }

        scrollToBottom();
    }

    // Render Kartu Buku Interaktif di dalam Chat
    function renderBookCards(books) {
        const cardWrapper = document.createElement('div');
        cardWrapper.className = 'w-full pl-9 pr-1 py-1 space-y-2';

        books.forEach(b => {
            const hasStock = parseInt(b.stok || 0) > 0;
            const stockBadge = hasStock 
                ? `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">✓ Stok: ${b.stok} eks</span>`
                : `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">✕ Stok Habis</span>`;

            const card = document.createElement('div');
            card.className = 'p-2.5 rounded-2xl bg-white border border-slate-200/90 shadow-sm hover:shadow-md transition-all flex gap-3 items-center';
            
            // Cover buku
            const coverImg = b.cover_image || 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=200&auto=format&fit=crop&q=80';
            
            card.innerHTML = `
                <img src="${coverImg}" alt="${b.judul}" class="w-14 h-20 object-cover rounded-xl shadow-sm shrink-0 border border-slate-100">
                <div class="flex-1 min-w-0">
                    <span class="text-[9px] font-extrabold uppercase tracking-wider text-primary block truncate">${b.nama_kategori || 'Katalog'}</span>
                    <h4 class="font-bold text-slate-900 text-xs truncate leading-snug" title="${b.judul}">${b.judul}</h4>
                    <p class="text-[10px] text-slate-500 truncate">${b.penulis || 'Penulis Bloom'}</p>
                    
                    <div class="flex items-center justify-between gap-1 mt-1.5 flex-wrap">
                        <span class="font-extrabold text-primary text-xs">${b.harga_formatted || ('Rp ' + b.harga)}</span>
                        ${stockBadge}
                    </div>

                    <div class="flex items-center gap-1.5 mt-2 pt-1 border-t border-slate-100">
                        <button type="button" class="btn-detail-book flex-1 py-1 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] transition-colors text-center"
                            data-book-id="${b.id}" data-book='${JSON.stringify(b).replace(/'/g, "&#39;")}'>
                            📖 Detail
                        </button>
                        
                        ${hasStock ? `
                            <form action="${CART_ACTION_URL}" method="POST" class="flex-1">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="book_id" value="${b.id}">
                                <input type="hidden" name="qty" value="1">
                                <button type="submit" class="w-full py-1 px-2 rounded-lg bg-gradient-to-r from-primary to-secondary hover:opacity-90 text-white font-bold text-[10px] shadow-sm transition-all text-center">
                                    + Beli
                                </button>
                            </form>
                        ` : ''}
                    </div>
                </div>
            `;

            // Event listener klik detail buku
            const detailBtn = card.querySelector('.btn-detail-book');
            detailBtn.addEventListener('click', () => {
                handleBookDetailClick(b);
            });

            cardWrapper.appendChild(card);
        });

        messagesContainer.appendChild(cardWrapper);
        scrollToBottom();
    }

    // Handler ketika user mengklik tombol detail buku pada kartu
    function handleBookDetailClick(book) {
        // Jika fungsi openBookDetail ada di halaman saat ini (misalnya di index.php)
        if (typeof window.openBookDetail === 'function') {
            window.openBookDetail(book);
            // Minimalkan chatbot pada layar kecil agar modal terlihat jelas
            if (window.innerWidth < 640) {
                toggleChatbot(false);
            }
        } else {
            // Jika berada di halaman lain (cart.php, user/orders.php), redirect ke index dengan view_book parameter
            window.location.href = `${BASE_INDEX_URL}?view_book=${book.id}#katalog`;
        }
    }

    // Render Quick Suggestion Chips
    function renderSuggestions(suggestions = []) {
        suggestionsContainer.innerHTML = '';
        if (!suggestions || suggestions.length === 0) {
            suggestionsContainer.classList.add('hidden');
            return;
        }

        suggestionsContainer.classList.remove('hidden');
        suggestions.forEach(s => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'px-3 py-1.5 rounded-full text-[11px] font-semibold bg-primary/10 hover:bg-primary text-primary hover:text-white transition-all whitespace-nowrap shrink-0 border border-primary/20 active:scale-95';
            btn.textContent = s;
            btn.addEventListener('click', () => {
                // Bersihkan emoji di awal chip jika perlu, atau kirim langsung
                const cleanQuery = s.replace(/^[^\w\s]+\s*/, '');
                sendMessage(cleanQuery);
            });
            suggestionsContainer.appendChild(btn);
        });
    }

    // Simpan riwayat percakapan ke LocalStorage
    function saveHistory() {
        try {
            // Batasi 25 pesan terakhir agar penyimpanan efisien
            const trimmed = chatHistory.slice(-25);
            localStorage.setItem(STORAGE_KEY, JSON.stringify(trimmed));
        } catch (e) {}
    }

    // Muat riwayat dari LocalStorage
    function loadHistory() {
        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                chatHistory = JSON.parse(saved);
                messagesContainer.innerHTML = '';
                chatHistory.forEach(msg => appendMessageBubble(msg));
                return true;
            }
        } catch (e) {}
        return false;
    }

    // Inisialisasi awal chatbot jika riwayat kosong
    async function initChatbot() {
        setTyping(true);
        try {
            const res = await fetch(`${CHATBOT_API_URL}?action=init`);
            const data = await res.json();

            if (data.status === 'success') {
                const botMsg = {
                    sender: 'bot',
                    text: data.reply,
                    time: formatTime(),
                    books: data.books || []
                };
                chatHistory.push(botMsg);
                appendMessageBubble(botMsg);
                renderSuggestions(data.suggestions || []);
                saveHistory();
            }
        } catch (err) {
            const fallbackMsg = {
                sender: 'bot',
                text: "Halo! Selamat datang di Bloom&Books. Ada yang bisa BloomBot bantu hari ini seputar buku dan stok? 😊",
                time: formatTime(),
                books: []
            };
            chatHistory.push(fallbackMsg);
            appendMessageBubble(fallbackMsg);
            renderSuggestions(["📚 Rekomendasi Terlaris", "📦 Cek Stok Buku", "💡 Novel Fiksi", "🛒 Cara Belanja"]);
            saveHistory();
        } finally {
            setTyping(false);
        }
    }

    // Kirim pesan ke Backend API
    async function sendMessage(text) {
        if (!text || text.trim() === '') return;

        const userMsg = {
            sender: 'user',
            text: text.trim(),
            time: formatTime()
        };

        // Render bubble pesan user
        chatHistory.push(userMsg);
        appendMessageBubble(userMsg);
        saveHistory();

        // Reset input
        input.value = '';
        setTyping(true);

        try {
            const response = await fetch(CHATBOT_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: text })
            });

            const data = await response.json();

            if (data.status === 'success') {
                const botMsg = {
                    sender: 'bot',
                    text: data.reply,
                    time: formatTime(),
                    books: data.books || []
                };
                chatHistory.push(botMsg);
                appendMessageBubble(botMsg);
                renderSuggestions(data.suggestions || []);
                saveHistory();
            } else {
                throw new Error("Invalid response");
            }
        } catch (err) {
            const errorMsg = {
                sender: 'bot',
                text: "Mohon maaf Kak, terjadi sedikit kendala koneksi ke server. Silakan coba kembali sesaat lagi. 🙏",
                time: formatTime(),
                books: []
            };
            chatHistory.push(errorMsg);
            appendMessageBubble(errorMsg);
            saveHistory();
        } finally {
            setTyping(false);
        }
    }

    // Event Listeners
    toggleBtn.addEventListener('click', () => toggleChatbot());
    closeBtn.addEventListener('click', () => toggleChatbot(false));

    // Bersihkan percakapan
    clearBtn.addEventListener('click', () => {
        if (confirm("Mulai percakapan baru dengan BloomBot?")) {
            localStorage.removeItem(STORAGE_KEY);
            chatHistory = [];
            messagesContainer.innerHTML = '';
            initChatbot();
        }
    });

    // Form Submit Listener
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const val = input.value.trim();
        if (val) {
            sendMessage(val);
        }
    });

    // Keyboard shortcut ESC untuk menutup chat
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !chatbotWindow.classList.contains('hidden')) {
            toggleChatbot(false);
        }
    });

    // Inisialisasi awal saat DOM siap
    document.addEventListener('DOMContentLoaded', () => {
        const hasSaved = loadHistory();
        if (!hasSaved) {
            // Suggestions default saat pertama kali load
            renderSuggestions([
                "📚 Rekomendasi Terlaris",
                "📦 Cek Stok Buku",
                "✨ Novel Fiksi",
                "💡 Buku Pengembangan Diri",
                "💰 Buku di Bawah 100rb"
            ]);
        }

        // Hilangkan tooltip setelah 7 detik
        if (tooltip) {
            setTimeout(() => {
                tooltip.style.opacity = '0';
                tooltip.style.transition = 'opacity 0.6s ease';
                setTimeout(() => tooltip.classList.add('hidden'), 600);
            }, 7000);
        }
    });

})();
</script>

