<div wire:ignore.self class="modal fade" id="theModal2" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header bg-dark">
          <h5 class="modal-title text-white text-uppercase">
            <b>{{$componentName}}</b> | Actualizar
          </h5>
          <h6 class="text-center text-warning" wire:loading>POR FAVOR ESPERE</h6>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-sm-12 col-md-6">
                    <div class="form-group">
                        <label>Referencia</label>
                        <input type="text" wire:model.lazy="reference" class="form-control" placeholder="Referencia del anticretico...">
                        @error('reference')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-sm-12 col-md-6">
                    <div class="form-group">
                        <label>Saldo actual</label>
                        <input type="text" wire:model.lazy="amount" class="form-control" placeholder="0.00" disabled>
                        @error('amount')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-sm-12 col-md-12">
                    <div class="form-group">
                        <label>Descripcion</label>
                        <textarea wire:model.lazy="description" class="form-control" placeholder="Detalles del movimiento..." cols="30" rows="3"></textarea>
                        @error('description')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-sm-12 col-md-6">
                    <div class="form-group">
                        <label>Accion</label>
                        <select wire:model="action" class="form-control">
                            <option value="Elegir">Elegir</option>
                            <option value= "Ingreso">Ingreso</option>
                            <option value= "Egreso">Egreso</option>
                        </select>
                        @error('action')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                @if($action != 'Elegir')

                <div class="col-sm-12 col-md-6">
                    <div class="form-group">
                        <label>Nuevo Monto</label>
                        <input type="text" wire:model.lazy="amount_2" class="form-control" placeholder="0.00">
                        @error('amount_2')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="col-sm-12 col-md-12">
                    <div class="form-group">
                        <label>Detalles</label>
                        <textarea wire:model.lazy="description_2" class="form-control" placeholder="Detalles del movimiento..." cols="30" rows="3"></textarea>
                        @error('description_2')
                            <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                @endif

            </div>

        </div>
        <div class="modal-footer">
            <button type="button" wire:click.prevent="resetUI()" class="btn btn-dark close-btn text-info" data-dismiss="modal">CERRAR</button>
            <button type="button" wire:click.prevent="Update()" class="btn btn-dark close-modal">ACTUALIZAR</button>
        </div>
    </div>
</div>
</div>