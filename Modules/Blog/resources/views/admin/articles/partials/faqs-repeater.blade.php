<div x-data="{ faqs: @json($article->faqs->sortBy('position')->values()->map(fn($f) => ['id' => $f->id, 'question' => $f->getTranslation('question', 'fr_CA', false), 'answer' => $f->getTranslation('answer', 'fr_CA', false), 'is_published' => (bool) $f->is_published])->all()) }">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">FAQ de l'article</h6>
        <button type="button" class="btn btn-sm btn-outline-primary"
                @click="faqs.push({ id: null, question: '', answer: '', is_published: true })">
            <i class="bi bi-plus-lg"></i> Ajouter une question
        </button>
    </div>

    <div class="text-muted small mb-3" x-show="faqs.length === 0">
        Aucune question pour le moment.
    </div>

    <template x-for="(faq, index) in faqs" :key="index">
        <div class="card mb-3">
            <div class="card-body">

                {{-- Hidden ID --}}
                <input type="hidden" :name="`faqs[${index}][id]`" :value="faq.id">

                {{-- Question --}}
                <div class="mb-2">
                    <label class="form-label fw-semibold" :for="`faq_question_${index}`">Question</label>
                    <input type="text"
                           class="form-control"
                           :id="`faq_question_${index}`"
                           :name="`faqs[${index}][question]`"
                           x-model="faq.question"
                           maxlength="300"
                           placeholder="Saisissez la question…">
                </div>

                {{-- Answer --}}
                <div class="mb-2">
                    <label class="form-label fw-semibold" :for="`faq_answer_${index}`">Réponse</label>
                    <textarea class="form-control"
                              :id="`faq_answer_${index}`"
                              :name="`faqs[${index}][answer]`"
                              x-model="faq.answer"
                              rows="3"
                              placeholder="Saisissez la réponse…"></textarea>
                </div>

                {{-- Is Published --}}
                <div class="form-check mb-3">
                    <input type="hidden" :name="`faqs[${index}][is_published]`" value="0">
                    <input class="form-check-input"
                           type="checkbox"
                           :id="`faq_published_${index}`"
                           :name="`faqs[${index}][is_published]`"
                           value="1"
                           :checked="faq.is_published"
                           @change="faq.is_published = $el.checked">
                    <label class="form-check-label" :for="`faq_published_${index}`">Publié</label>
                </div>

                {{-- Actions --}}
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            :disabled="index === 0"
                            @click="if (index > 0) { let tmp = faqs[index]; faqs[index] = faqs[index - 1]; faqs[index - 1] = tmp; }">
                        ↑ Monter
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            :disabled="index === faqs.length - 1"
                            @click="if (index < faqs.length - 1) { let tmp = faqs[index]; faqs[index] = faqs[index + 1]; faqs[index + 1] = tmp; }">
                        ↓ Descendre
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger ms-auto"
                            @click="$dispatch('confirm-action', { title: @js(__('Confirmer')), message: @js(__('Supprimer cette question ?')), action: () => faqs.splice(index, 1) })">
                        Supprimer
                    </button>
                </div>

            </div>
        </div>
    </template>

</div>
