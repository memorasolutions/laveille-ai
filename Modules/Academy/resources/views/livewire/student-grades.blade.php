<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    @if ($this->grades->isNotEmpty())
        <section aria-labelledby="student-grades" style="margin-top: 8px;">
            <h2 id="student-grades" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 12px; font-size: 1.25rem;">
                <span aria-hidden="true">📊</span> Mes notes
            </h2>

            <ul class="list-unstyled d-flex flex-column gap-3" style="margin: 0;">
                @foreach ($this->grades as $row)
                    <li wire:key="grade-{{ $row['course']->id }}"
                        style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 16px 18px;">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <h3 style="font-family: var(--f-heading); font-size: 1.05rem; color: var(--sys-text-default, #1A1D23); margin: 0;">
                                {{ $row['course']->title }}
                            </h3>
                            <div style="font-size: 0.95rem;">
                                <span style="font-weight: 700; color: var(--sys-action-primary, #064E5A);">Note finale : {{ $row['final'] }}%</span>
                                <span style="font-weight: 700; margin-left: 8px;">({{ $row['letter'] }})</span>
                            </div>
                        </div>

                        @if (count($row['categories']) > 0)
                            <ul class="list-unstyled d-flex flex-column gap-1" style="margin: 10px 0 0; font-size: 0.85rem;">
                                @foreach ($row['categories'] as $cat)
                                    <li class="d-flex flex-wrap justify-content-between gap-2" style="color: var(--sys-text-muted, #6B7280);">
                                        <span>{{ $cat['name'] }} <span style="opacity: 0.8;">(poids {{ rtrim(rtrim(number_format($cat['weight'], 2, '.', ''), '0'), '.') }} %)</span></span>
                                        <span>
                                            @if ($cat['hasData'])
                                                {{ round($cat['score'], 1) }}%
                                            @else
                                                <span style="color: var(--sys-text-muted, #9CA3AF);">non évalué</span>
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
