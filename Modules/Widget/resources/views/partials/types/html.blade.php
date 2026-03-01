<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div class="widget widget-html mb-3">
    <h5 class="widget-title">{{ $widget->title }}</h5>
    <div class="widget-content">{!! Purifier::clean($widget->content) !!}</div>
</div>
