<section id="hrd-panel-blog-form" class="view-panel view-hidden">
                    <form id="hrd-form-blog" class="bg-white/80 rounded-2xl p-6 border space-y-4 max-w-2xl">
                        <h3 id="hrd-blog-form-title" class="font-bold text-lg">Artikel baru</h3>
                        <input type="hidden" id="hrd-blog-id">
                        <input type="text" id="hrd-blog-title" required maxlength="200" placeholder="Judul" class="w-full px-4 py-3 rounded-xl border">
                        <textarea id="hrd-blog-body" rows="8" maxlength="8000" placeholder="Isi artikel..." class="w-full px-4 py-3 rounded-xl border"></textarea>
                        <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" id="hrd-blog-published"> Tayangkan di portal pelamar</label>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="blog-photo-slot" data-blog-photo-idx="0"><i class="fa-solid fa-camera"></i></div>
                            <div class="blog-photo-slot" data-blog-photo-idx="1"><i class="fa-solid fa-image"></i></div>
                            <div class="blog-photo-slot" data-blog-photo-idx="2"><i class="fa-solid fa-image"></i></div>
                        </div>
                        <div class="flex gap-3"><button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-bold">Simpan</button><a href="#blog" class="px-5 py-2.5 rounded-xl border font-bold">Batal</a></div>
                    </form>
                </section>