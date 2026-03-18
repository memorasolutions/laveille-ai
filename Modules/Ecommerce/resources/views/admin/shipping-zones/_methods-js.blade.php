<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('methods-container');
    const addBtn = document.getElementById('add-method-btn');

    function createRow(idx) {
        return `
        <div class="card mb-3 border method-row" id="method-row-${idx}">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between mb-2">
                    <h6 class="fw-bold text-muted small">Nouvelle méthode</h6>
                    <button type="button" class="btn btn-xs btn-danger btn-icon remove-method" data-target="method-row-${idx}">
                        <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                    </button>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small">Nom</label>
                        <input type="text" name="methods[n${idx}][name]" class="form-control form-control-sm" placeholder="Ex: Standard" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Type</label>
                        <select name="methods[n${idx}][type]" class="form-select form-select-sm">
                            <option value="flat_rate">Forfaitaire</option>
                            <option value="free">Gratuit</option>
                            <option value="per_weight">Par poids</option>
                            <option value="percentage">Pourcentage</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Coût</label>
                        <input type="number" step="0.01" name="methods[n${idx}][cost]" class="form-control form-control-sm" placeholder="0.00">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Min.</label>
                        <input type="number" step="0.01" name="methods[n${idx}][min_order]" class="form-control form-control-sm" placeholder="Optionnel">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Max.</label>
                        <input type="number" step="0.01" name="methods[n${idx}][max_order]" class="form-control form-control-sm" placeholder="Optionnel">
                    </div>
                </div>
            </div>
        </div>`;
    }

    addBtn.addEventListener('click', function() {
        container.insertAdjacentHTML('beforeend', createRow(Date.now()));
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    container.addEventListener('click', function(e) {
        const btn = e.target.closest('.remove-method');
        if (btn) document.getElementById(btn.dataset.target).remove();
    });
});
</script>
