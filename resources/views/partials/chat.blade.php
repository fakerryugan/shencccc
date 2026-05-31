<section id="hrd-panel-chat" class="view-panel view-hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,20rem)_1fr] gap-4 min-h-[min(32rem,70vh)]">
                        <div class="bg-white/80 rounded-2xl border flex flex-col overflow-hidden">
                            <div class="p-4 border-b border-slate-100">
                                <h3 class="font-bold text-sm">Semua obrolan</h3>
                                <p class="text-[10px] text-slate-500 mt-0.5">Pilih pelamar untuk membalas.</p>
                                <input type="search" id="hrd-chat-inbox-search" placeholder="Cari nama..." class="mt-3 w-full px-3 py-2 rounded-lg border text-sm">
                            </div>
                            <div id="hrd-chat-inbox-list" class="flex-1 overflow-y-auto divide-y divide-slate-100"></div>
                            <p id="hrd-chat-inbox-empty" class="hidden p-6 text-center text-xs text-slate-500">Belum ada percakapan. Kirim pesan dari profil pelamar.</p>
                            <div id="hrd-chat-list-footer" class="list-page-footer hidden border-t border-slate-100">
                                <p id="hrd-chat-list-count" class="text-center text-[10px] text-slate-500 mb-2 px-2"></p>
                                <button type="button" id="hrd-btn-chat-load-more" class="btn-load-more-list mx-2 mb-2">Muat lebih banyak</button>
                            </div>
                        </div>
                        <div class="bg-white/80 rounded-2xl border flex flex-col overflow-hidden">
                            <div id="hrd-chat-hub-header" class="p-4 border-b border-slate-100">
                                <p class="text-sm text-slate-500">Pilih pelamar di daftar kiri.</p>
                            </div>
                            <div id="hrd-chat-hub-thread" class="chat-hub-thread flex-1 p-4 space-y-2 bg-slate-50/50"></div>
                            <form id="hrd-chat-hub-form" class="hidden p-4 border-t border-slate-100 flex gap-2">
                                <input type="text" id="hrd-chat-hub-input" maxlength="2000" placeholder="Tulis pesan ke pelamar..." class="flex-1 px-3 py-2 rounded-lg border text-sm">
                                <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-bold shrink-0"><i class="fa-solid fa-paper-plane"></i></button>
                            </form>
                        </div>
                    </div>
                </section>