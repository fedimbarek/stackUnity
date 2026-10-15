<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ajouter un équipement</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                <div style="padding:24px 28px; border-bottom:1px solid #e5e7eb;">
                    <h3 style="margin:0; color:#1f2937; font-size:20px; font-weight:700;">Informations de l’équipement</h3>
                    <p style="margin:6px 0 0; color:#6b7280;">Renseignez les détails et ajoutez une image pour présenter l’équipement.</p>
                </div>

                <div style="padding:28px;">
                <form action="{{ route('admin.equipements.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:20px 24px;">
                    <div>
                        <label for="nom" class="block text-sm font-medium text-gray-700">Nom <span class="text-red-600">*</span></label>
                        <input id="nom" name="nom" type="text" value="{{ old('nom') }}" required maxlength="255"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('nom') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="type_equipement" class="block text-sm font-medium text-gray-700">Type d'équipement <span class="text-red-600">*</span></label>
                        <input id="type_equipement" name="type_equipement" type="text" value="{{ old('type_equipement') }}" required maxlength="255"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('type_equipement') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="date_ajout" class="block text-sm font-medium text-gray-700">Date d'ajout <span class="text-red-600">*</span></label>
                        <input id="date_ajout" name="date_ajout" type="date" value="{{ old('date_ajout', now()->toDateString()) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('date_ajout') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="prix_louer" class="block text-sm font-medium text-gray-700">Prix de location (DT) <span class="text-red-600">*</span></label>
                        <input id="prix_louer" name="prix_louer" type="number" value="{{ old('prix_louer') }}" required min="0" step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('prix_louer') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    </div>

                    <div style="margin-top:28px;">
                        <label for="image" class="block text-sm font-medium text-gray-700">Image de l’équipement <span class="font-normal text-gray-500">(facultative, 2 Mo maximum)</span></label>
                        <div style="margin-top:8px; padding:18px; border:2px dashed #cbd5e1; border-radius:12px; background:#f8fafc;">
                            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:20px;">
                                <div style="width:180px; height:140px; flex-shrink:0; overflow:hidden; border:1px solid #e5e7eb; border-radius:8px; background:white;">
                                    <img id="image-preview" alt="Aperçu de l’image sélectionnée" hidden style="width:100%; height:100%; object-fit:cover;">
                                    <div id="image-placeholder" style="display:flex; width:100%; height:100%; align-items:center; justify-content:center; color:#94a3b8; font-size:14px;">
                                        Aperçu de l’image
                                    </div>
                                </div>
                                <div style="min-width:220px; flex:1;">
                        <input id="image" name="image" type="file" accept="image/*"
                            class="block w-full rounded-md border border-gray-300 bg-white text-sm text-gray-700 shadow-sm file:mr-4 file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:font-medium file:text-indigo-700 hover:file:bg-indigo-100" />
                                    <p class="mt-2 text-xs text-gray-500">Formats image acceptés. Taille maximale : 2 Mo.</p>
                                </div>
                            </div>
                        </div>
                        @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div style="display:flex; align-items:center; gap:12px; margin-top:28px; padding-top:20px; border-top:1px solid #e5e7eb;">
                        <button type="submit" class="btn btn-primary">Enregistrer l'équipement</button>
                        <a href="{{ route('admin.equipements.index') }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
    <script>
        document.getElementById('image').addEventListener('change', function (event) {
            const file = event.target.files[0];
            const preview = document.getElementById('image-preview');
            const placeholder = document.getElementById('image-placeholder');

            if (!file) {
                preview.removeAttribute('src');
                preview.hidden = true;
                placeholder.style.display = 'flex';
                return;
            }

            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
            placeholder.style.display = 'none';
        });
    </script>
@endpush