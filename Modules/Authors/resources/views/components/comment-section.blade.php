@props(['commentableType', 'commentableId', 'authorProfileId'])

@livewire('authors.comment-section', [
    'commentableType' => $commentableType,
    'commentableId' => $commentableId,
    'authorProfileId' => $authorProfileId,
], key('comments-'.md5($commentableType).'-'.$commentableId))
