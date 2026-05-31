<section id="hrd-panel-pelamar" class="view-panel view-hidden space-y-4">
                    <div class="bg-white/80 rounded-2xl p-4 border flex flex-col md:flex-row gap-3 flex-wrap">
                        <input type="search" id="hrd-filter-search" placeholder="Cari nama, posisi, WA..." class="flex-1 min-w-[200px] px-4 py-2.5 rounded-xl border text-sm">
                        <select id="hrd-filter-status" class="px-4 py-2.5 rounded-xl border text-sm"></select>
                        <select id="hrd-filter-posisi" class="px-4 py-2.5 rounded-xl border text-sm"><option value="">Semua posisi</option></select>
                        <select id="hrd-filter-fase-undangan" class="px-4 py-2.5 rounded-xl border text-sm" title="Fase undangan wawancara">
                            <option value="">Semua fase undangan</option>
                            <option value="undangan">Sedang diundang</option>
                            <option value="undangan_selesai">Undangan ditutup</option>
                            <option value="belum_diundang">Belum diundang</option>
                        </select>
                        <select id="hrd-filter-rsvp-undangan" class="px-4 py-2.5 rounded-xl border text-sm" title="Konfirmasi kehadiran wawancara">
                            <option value="">Semua konfirmasi</option>
                            <option value="hadir">Hadir</option>
                            <option value="tidak_hadir">Tidak bisa hadir</option>
                            <option value="tidak_merespon">Tidak merespon</option>
                        </select>
                        <datalist id="hrd-posisi-datalist"></datalist>
                        <button type="button" id="hrd-btn-pelamar-refresh" class="px-4 py-2.5 rounded-xl border border-violet-300 text-violet-800 text-sm font-bold whitespace-nowrap hover:bg-violet-50" title="Ambil data terbaru dari server (hemat kuota: tidak realtime otomatis)">
                            <i class="fa-solid fa-rotate mr-1"></i> Muat ulang
                        </button>
                    </div>
                    <p id="hrd-pelamar-list-hint" class="text-[11px] text-slate-500 -mt-2 px-1">Daftar tidak auto-refresh. Klik <strong>Muat ulang</strong> setelah ada perubahan data.</p>
                    <div class="bg-white/80 rounded-2xl border overflow-hidden">
                        <div id="hrd-pelamar-card-list"></div>
                        <p id="hrd-pelamar-empty" class="hidden p-8 text-center text-slate-500">Belum ada pelamar.</p>
                        <div id="hrd-pelamar-list-footer" class="list-page-footer hidden">
                            <p id="hrd-pelamar-list-count" class="text-center text-xs text-slate-500 mb-2"></p>
                            <button type="button" id="hrd-btn-pelamar-load-more" class="btn-load-more-list">Muat lebih banyak</button>
                        </div>
                    </div>
                </section>