</main>
</div>
</div><?php if (user()['role'] === 'content_manager'): ?><div class="modal fade" id="aiDraftModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" id="aiDraftForm">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title fs-5"><i class="bi bi-stars text-info"></i> Generate with AI</h2><small class="text-muted">AI creates a draft only. Review it before saving.</small>
                    </div><button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="kind" id="aiKind"><input type="hidden" name="target_id" id="aiTargetId">
                    <div id="aiTargetWrap" class="mb-3 d-none"><label class="form-label">Career content</label><select class="form-select" id="aiTargetSelect"></select></div>
                    <div id="aiCareerContext" class="alert alert-info py-2 d-none"><i class="bi bi-briefcase me-1"></i> Generating specifically for <strong id="aiCareerName"></strong></div>
                    <div id="aiInterestWrap" class="mb-3 d-none"><label class="form-label">RIASEC type</label><select class="form-select" name="interest_type" id="aiInterestType"><?php foreach (['realistic', 'investigative', 'artistic', 'social', 'enterprising', 'conventional'] as $type): ?><option value="<?= $type ?>"><?= ucfirst($type) ?></option><?php endforeach ?></select></div>
                    <div class="row g-3">
                        <div class="col-md-8"><label class="form-label">Topic</label><input class="form-control" name="topic" required maxlength="300" placeholder="e.g. handling a difficult client professionally"></div>
                        <div class="col-md-4"><label class="form-label">Difficulty</label><select class="form-select" name="difficulty">
                                <option>beginner</option>
                                <option>intermediate</option>
                                <option>advanced</option>
                            </select></div>
                    </div>
                    <div id="aiDraftReview" class="d-none mt-4"><label class="form-label fw-semibold">Review and edit the generated draft</label><textarea class="form-control font-monospace" id="aiDraftJson" name="draft_json" rows="16"></textarea>
                        <div class="form-text">Nothing is added until you select “Save reviewed draft.”</div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-outline-primary" id="aiGenerateButton"><i class="bi bi-stars"></i> Generate draft</button><button type="button" class="btn btn-primary d-none" id="aiSaveButton">Save reviewed draft</button></div>
            </form>
        </div>
    </div><?php endif ?><div class="modal fade" id="contentEditorModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5">Manage content</h2><button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contentEditorBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastArea"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
<script>
    document.getElementById('adminGlobalSearch')?.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(row => row.hidden = !row.textContent.toLowerCase().includes(q))
    });
    document.getElementById('sidebarCollapse')?.addEventListener('click', function() {
        const collapsed = document.documentElement.classList.toggle('sidebar-is-collapsed');
        localStorage.setItem('careerSimSidebar', collapsed ? 'collapsed' : 'expanded');
        this.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        this.title = collapsed ? 'Expand sidebar' : 'Collapse sidebar'
    });
    document.addEventListener('click', async function(event) {
        const link = event.target.closest('a.content-editor-link');
        if (!link) return;
        event.preventDefault();
        const modalElement = document.getElementById('contentEditorModal'),
            body = document.getElementById('contentEditorBody'),
            modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        body.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        modal.show();
        try {
            const response = await fetch(link.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) throw new Error('The content form could not be loaded.');
            const html = await response.text(),
                doc = new DOMParser().parseFromString(html, 'text/html'),
                card = doc.querySelector('.content-editor-card');
            if (!card) throw new Error('The form could not be loaded.');
            card.classList.remove('card');
            card.classList.remove('mx-auto');
            body.innerHTML = '';
            body.append(...card.childNodes);
            modalElement.querySelector('.modal-title').textContent = body.querySelector('h1')?.textContent || 'Manage content';
            body.querySelector('h1')?.remove();
            syncSimulationQuiz()
        } catch (error) {
            body.innerHTML = '<div class="alert alert-danger">' + escapeHtml(error.message) + '</div>'
        }
    });
    document.addEventListener('submit', async function(event) {
        const form = event.target.closest('.content-editor-form');
        if (!form) return;
        event.preventDefault();
        const button = form.querySelector('[type=submit]');
        if (button) button.disabled = true;
        try {
            const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': APP.csrf
                    }
                }),
                data = await response.json();
            if (!response.ok || !data.ok) throw new Error(data.message || 'Could not save.');
            toast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('contentEditorModal'))?.hide();
            setTimeout(() => location.reload(), 450)
        } catch (error) {
            toast(error.message, 'danger');
            if (button) button.disabled = false
        }
    });
    document.addEventListener('click', async function(event) {
        const button = event.target.closest('.career-ai-content');
        if (!button) return;
        const form = button.closest('form'),
            title = form?.elements.title?.value.trim();
        if (!title) {
            toast('Enter the career title first.', 'warning');
            form?.elements.title?.focus();
            return
        }
        const original = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating\u2026';
        try {
            const body = new FormData();
            body.set('csrf', APP.csrf);
            body.set('title', title);
            const response = await fetch(APP.base + 'admin/ai_career_content.php', {
                    method: 'POST',
                    body,
                    headers: {'X-CSRF-Token': APP.csrf}
                }),
                data = await response.json();
            if (!response.ok || !data.ok) throw new Error(data.message || 'AI is temporarily unavailable.');
            form.elements.summary.value = data.summary;
            form.elements.description.value = data.description;
            form.elements.summary.dispatchEvent(new Event('input', {bubbles: true}));
            form.elements.description.dispatchEvent(new Event('input', {bubbles: true}));
            toast(data.message, 'success');
            form.elements.summary.focus()
        } catch (error) {
            toast(error.message || 'AI is temporarily unavailable.', 'danger')
        } finally {
            button.disabled = false;
            button.innerHTML = original
        }
    });
    document.addEventListener('click', function(event) {
        const button = event.target.closest('.ai-generate');
        if (!button) return;
        const form = document.getElementById('aiDraftForm');
        form.reset();
        document.getElementById('aiKind').value = button.dataset.kind;
        const targetSelect = document.getElementById('aiTargetSelect'),
            targetWrap = document.getElementById('aiTargetWrap');
        targetSelect.innerHTML = '';
        let options = [];
        try {
            options = JSON.parse(button.dataset.options || '[]')
        } catch (e) {};
        targetWrap.classList.toggle('d-none', !options.length);
        options.forEach(option => targetSelect.add(new Option(option.label, option.id)));
        document.getElementById('aiTargetId').value = button.dataset.target || options[0]?.id || 0;
        targetSelect.onchange = () => document.getElementById('aiTargetId').value = targetSelect.value;
        const context = document.getElementById('aiCareerContext');
        context.classList.toggle('d-none', !button.dataset.context);
        document.getElementById('aiCareerName').textContent = button.dataset.context || '';
        document.getElementById('aiInterestWrap').classList.toggle('d-none', button.dataset.kind !== 'interest_questions');
        document.getElementById('aiDraftReview').classList.add('d-none');
        document.getElementById('aiSaveButton').classList.add('d-none');
        document.getElementById('aiGenerateButton').classList.remove('d-none');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('aiDraftModal')).show()
    });
    document.getElementById('aiDraftForm')?.addEventListener('submit', async function(event) {
        event.preventDefault();
        const button = document.getElementById('aiGenerateButton');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating…';
        try {
            const response = await fetch(APP.base + 'admin/ai_generate.php', {
                    method: 'POST',
                    body: new FormData(this),
                    headers: {
                        'X-CSRF-Token': APP.csrf
                    }
                }),
                data = await response.json();
            if (!response.ok || !data.ok) throw new Error(data.message || 'AI is temporarily unavailable.');
            document.getElementById('aiDraftJson').value = data.draft_json;
            document.getElementById('aiDraftReview').classList.remove('d-none');
            document.getElementById('aiSaveButton').classList.remove('d-none')
        } catch (error) {
            toast(error.message, 'danger')
        } finally {
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-stars"></i> Regenerate draft'
        }
    });
    document.getElementById('aiSaveButton')?.addEventListener('click', async function() {
        const form = document.getElementById('aiDraftForm'),
            body = new FormData(form);
        this.disabled = true;
        try {
            const response = await fetch(APP.base + 'admin/ai_save_draft.php', {
                    method: 'POST',
                    body,
                    headers: {
                        'X-CSRF-Token': APP.csrf
                    }
                }),
                data = await response.json();
            if (!response.ok || !data.ok) throw new Error(data.message || 'Could not save the draft.');
            toast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('aiDraftModal'))?.hide();
            setTimeout(() => location.reload(), 500)
        } catch (error) {
            toast(error.message, 'danger');
            this.disabled = false
        }
    });

    function syncSimulationQuiz() {
        const career = document.getElementById('contentCareer'),
            quiz = document.getElementById('simulationQuiz');
        if (!career || !quiz) return;
        let first = null;
        [...quiz.options].forEach(option => {
            option.hidden = option.dataset.career !== career.value;
            if (!option.hidden && !first) first = option
        });
        if (quiz.selectedOptions[0]?.hidden && first) quiz.value = first.value;
        const selected = quiz.selectedOptions[0],
            form = quiz.closest('form');
        document.getElementById('selectedQuizTitle').textContent = selected?.dataset.title || 'No quiz available for this career';
        document.getElementById('selectedQuizDescription').textContent = selected?.dataset.description || '';
        if (form && !form.elements.id?.value && selected) {
            form.elements.title.value = selected.dataset.title + ' Simulation';
            form.elements.description.value = selected.dataset.description || ''
        }
    }
    document.addEventListener('change', event => {
        if (event.target.matches('#contentCareer,#simulationQuiz')) syncSimulationQuiz()
    });
    document.addEventListener('click', event => {
        const button = event.target.closest('.ai-generate');
        if (!button) return;
        setTimeout(() => {
            const topic = document.querySelector('#aiDraftForm [name=topic]'),
                label = topic?.closest('div')?.querySelector('label');
            if (!topic) return;
            let options = [];
            try {
                options = JSON.parse(button.dataset.options || '[]')
            } catch (e) {};
            const usesLinkedContent = button.dataset.kind === 'simulation_tasks' || options.some(item => item.difficulty);
            topic.required = !usesLinkedContent;
            topic.placeholder = usesLinkedContent ? 'Optional focus — quiz description is used automatically' : 'e.g. handling a difficult client professionally';
            if (label) label.textContent = usesLinkedContent ? 'Focus topic (optional)' : 'Topic';
            const target = document.getElementById('aiTargetSelect'),
                difficulty = document.querySelector('#aiDraftForm [name=difficulty]');

            function useLinkedLevel() {
                const selected = options.find(item => String(item.id) === String(target.value));
                if (selected?.difficulty) difficulty.value = selected.difficulty
            }
            target.addEventListener('change', useLinkedLevel, {
                once: false
            });
            useLinkedLevel()
        }, 0)
    });
    document.addEventListener('click', event => {
        const button = event.target.closest('.ai-generate[data-create-simulation]');
        if (!button) return;
        const immediateTopic = document.querySelector('#aiDraftForm [name=topic]');
        immediateTopic?.closest('[class*=col-md]')?.classList.add('d-none');
        setTimeout(() => {
            const form = document.getElementById('aiDraftForm'),
                targetWrap = document.getElementById('aiTargetWrap'),
                target = document.getElementById('aiTargetSelect'),
                topic = form.querySelector('[name=topic]'),
                topicColumn = topic.closest('[class*=col-md]');
            topic.value = '';
            topic.required = false;
            topicColumn.classList.add('d-none');
            topicColumn.nextElementSibling?.classList.replace('col-md-4', 'col-md-12');
            let options = [];
            try {
                options = JSON.parse(button.dataset.options || '[]')
            } catch (e) {};
            let hidden = form.querySelector('[name=create_simulation]');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'create_simulation';
                form.append(hidden)
            }
            hidden.value = '1';
            let careerWrap = document.getElementById('aiSimulationCareerWrap');
            if (!careerWrap) {
                careerWrap = document.createElement('div');
                careerWrap.id = 'aiSimulationCareerWrap';
                careerWrap.className = 'mb-3';
                careerWrap.innerHTML = '<label class="form-label">Career</label><select class="form-select" id="aiSimulationCareer"></select>';
                targetWrap.before(careerWrap)
            }
            const careerSelect = document.getElementById('aiSimulationCareer'),
                careers = [...new Map(options.map(item => [String(item.career_id), {
                    id: item.career_id,
                    name: item.career
                }])).values()];
            careerSelect.innerHTML = '';
            careers.forEach(career => careerSelect.add(new Option(career.name, career.id)));
            targetWrap.querySelector('label').textContent = 'Quiz';

            function filterQuizzes() {
                const available = options.filter(item => String(item.career_id) === careerSelect.value);
                target.innerHTML = '';
                available.forEach(item => target.add(new Option(item.label, item.id)));
                document.getElementById('aiTargetId').value = target.value || 0;
                target.dispatchEvent(new Event('change'))
            }
            careerSelect.onchange = filterQuizzes;
            filterQuizzes()
        }, 0)
    });
    document.addEventListener('click', event => {
        const button = event.target.closest('.ai-generate');
        if (!button || button.dataset.createSimulation) return;
        document.querySelector('#aiDraftForm [name=create_simulation]')?.remove();
        document.getElementById('aiSimulationCareerWrap')?.remove();
        const label = document.querySelector('#aiTargetWrap label'),
            topic = document.querySelector('#aiDraftForm [name=topic]'),
            topicColumn = topic?.closest('[class*=col-md]');
        if (label) label.textContent = 'Career content';
        topicColumn?.classList.remove('d-none');
        topicColumn?.nextElementSibling?.classList.replace('col-md-12', 'col-md-4')
    });
</script>
</body>

</html>
