<div class="card shadow-sm mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-comments text-primary me-2"></i>
            Communication Timeline
        </h5>

        <button type="button" class="btn btn-primary btn-sm" id="showCommFormBtn">
            Add New
        </button>
    </div>

    <div class="card-body">

        <form method="post" action="<?= BASE_URL ?>modules/communication_save.php" id="communicationForm" style="display:none;">
            <input type="hidden" name="client_id" value="<?= (int)$client['id'] ?>">
            <input type="hidden" name="company_id" value="<?= (int)$client['company_id'] ?>">
            <input type="hidden" name="communication_type" value="Meeting">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Who</label>
                    <select name="communication_by" id="communicationBy" class="form-select" required>
                        <option value="">Select</option>
                        <option value="Client">Client</option>
                        <option value="Unire">Unire</option>
                    </select>
                </div>

                <div class="col-md-8">
                    <label class="form-label" id="communicationLabel">Current Response</label>
                    <textarea name="communication" id="communicationText" class="form-control" rows="4" required></textarea>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-success">Save</button>
                <button type="button" class="btn btn-secondary" id="cancelCommFormBtn">Cancel</button>
            </div>
        </form>

        <hr>

        <!-- your existing timeline loop here -->
    </div>
</div>

<script>
document.getElementById('showCommFormBtn').addEventListener('click', function () {
    document.getElementById('communicationForm').style.display = 'block';
    this.style.display = 'none';
});

document.getElementById('cancelCommFormBtn').addEventListener('click', function () {
    document.getElementById('communicationForm').style.display = 'none';
    document.getElementById('showCommFormBtn').style.display = 'inline-block';
    document.getElementById('communicationBy').value = '';
    document.getElementById('communicationLabel').textContent = 'Current Response';
    document.getElementById('communicationText').value = '';
});

document.getElementById('communicationBy').addEventListener('change', function () {
    const label = document.getElementById('communicationLabel');

    if (this.value === 'Client') {
        label.textContent = 'What replied from client?';
    } else if (this.value === 'Unire') {
        label.textContent = 'What is discussed in meeting?';
    } else {
        label.textContent = 'Current Response';
    }
});
</script>