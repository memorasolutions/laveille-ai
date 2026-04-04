{{-- Welcome email = digest-weekly avec sections bienvenue --}}
{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@include('newsletter::emails.digest-weekly', array_merge(get_defined_vars(), ['isWelcome' => true]))
