<!-- Modal estructura -->
<div class="modal fade" id="{{ $id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-{{ $size }}">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h5 class="modal-title">{{ $titulo }}</h5>
                <!--<button type="button" class="btn-close bg-white" data-bs-dismiss="modal" aria-label="Close"></button>-->
            </div>
            <div class="modal-body">
                <div id="modalContent">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>