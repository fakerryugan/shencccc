<section id="hrd-panel-blog" class="view-panel view-hidden space-y-6">
                    <div class="flex flex-wrap gap-2 justify-end">
                        <a href="#broadcast/baru" class="px-4 py-2 rounded-xl bg-amber-500 text-white text-sm font-bold shadow-sm"><i class="fa-solid fa-bullhorn mr-1"></i> Broadcast baru</a>
                        <a href="#blog/baru" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold"><i class="fa-solid fa-plus mr-1"></i> Artikel baru</a>
                    </div>
                    <div>
                        <div class="flex flex-wrap gap-3 justify-between items-center mb-3">
                            <h3 class="font-bold text-slate-700"><i class="fa-solid fa-newspaper text-indigo-500 mr-2"></i>Artikel</h3>
                            <select id="hrd-blog-filter" class="px-4 py-2 rounded-xl border text-sm"><option value="">Semua</option><option value="published">Tayang</option><option value="draft">Draft</option></select>
                        </div>
                    <div id="hrd-blog-card-list" class="grid sm:grid-cols-2 gap-4"></div>
                        <p id="hrd-blog-empty" class="hidden text-center text-slate-500 py-8">Belum ada artikel.</p>
                        <div id="hrd-blog-list-footer" class="list-page-footer hidden">
                            <p id="hrd-blog-list-count" class="text-center text-xs text-slate-500 mb-2"></p>
                            <button type="button" id="hrd-btn-blog-load-more" class="btn-load-more-list">Muat lebih banyak</button>
                        </div>
                    </div>
                    <div class="border-t border-slate-200 pt-6">
                        <div class="flex flex-wrap gap-3 justify-between items-center mb-3">
                            <h3 class="font-bold text-slate-700"><i class="fa-solid fa-bullhorn text-amber-500 mr-2"></i>Broadcast popup</h3>
                            <select id="hrd-broadcast-filter" class="px-4 py-2 rounded-xl border text-sm"><option value="">Semua</option><option value="published">Tayang</option><option value="draft">Draft</option><option value="active">Jadwal aktif hari ini</option></select>
                        </div>
                        <p class="text-xs text-slate-500 mb-3">Popup untuk semua pengunjung, 1-2 menit setelah buka app, maks. 2x/hari (pagi & malam WIB).</p>
                        <div id="hrd-broadcast-card-list" class="grid sm:grid-cols-2 gap-4"></div>
                        <p id="hrd-broadcast-empty" class="hidden text-center text-slate-500 py-8">Belum ada broadcast.</p>
                        <div id="hrd-broadcast-list-footer" class="list-page-footer hidden">
                            <p id="hrd-broadcast-list-count" class="text-center text-xs text-slate-500 mb-2"></p>
                            <button type="button" id="hrd-btn-broadcast-load-more" class="btn-load-more-list">Muat lebih banyak</button>
                        </div>
                    </div>
                </section>