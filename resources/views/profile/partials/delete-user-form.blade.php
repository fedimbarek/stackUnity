<section>
    <h6 class="m-0 font-weight-bold text-danger mb-3">Supprimer le compte</h6>
    <p class="small text-gray-600">Cette action est irréversible.</p>

    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#confirmUserDeletion">
        Supprimer le compte
    </button>

    <div class="modal fade" id="confirmUserDeletion" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmer la suppression</h5>
                        <button class="close" type="button" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="password" name="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" placeholder="Mot de passe">
                        @error('password', 'userDeletion') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>