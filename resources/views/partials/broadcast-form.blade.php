<section id="hrd-panel-broadcast-form" class="view-panel view-hidden">
                    <form id="hrd-form-broadcast" class="bg-white/80 rounded-2xl p-6 border space-y-4 max-w-2xl">
                        <h3 id="hrd-broadcast-form-title" class="font-bold text-lg">Broadcast baru</h3>
                        <input type="hidden" id="hrd-broadcast-id">
                        <input type="text" id="hrd-broadcast-title" required maxlength="200" placeholder="Judul" class="w-full px-4 py-3 rounded-xl border">
                        <textarea id="hrd-broadcast-body" rows="8" maxlength="8000" placeholder="Isi broadcast..." class="w-full px-4 py-3 rounded-xl border"></textarea>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div><label class="text-xs font-bold uppercase text-slate-500">Tayang dari</label><input type="date" id="hrd-broadcast-schedule-start" required class="mt-1 w-full px-4 py-3 rounded-xl border"></div>
                            <div><label class="text-xs font-bold uppercase text-slate-500">Sampai</label><input type="date" id="hrd-broadcast-schedule-end" required class="mt-1 w-full px-4 py-3 rounded-xl border"></div>
                        </div>
                        <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" id="hrd-broadcast-published"> Aktifkan popup di portal pelamar</label>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="blog-photo-slot" data-broadcast-photo-idx="0"><i class="fa-solid fa-camera"></i></div>
                            <div class="blog-photo-slot" data-broadcast-photo-idx="1"><i class="fa-solid fa-image"></i></div>
                            <div class="blog-photo-slot" data-broadcast-photo-idx="2"><i class="fa-solid fa-image"></i></div>
                        </div>
                        <div class="flex gap-3"><button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-500 text-white font-bold">Simpan</button><a href="#blog" class="px-5 py-2.5 rounded-xl border font-bold">Batal</a></div>
                    </form>
                </section>