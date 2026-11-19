{{-- Campaign module navigation. Expects $active. --}}
@include('user._ui-kit')
<nav class="ui-tabs cnm-ui" aria-label="Campaigns">
    @foreach([['campaigns', 'user.campaigns.index', 'My campaigns', 'fa-bullhorn'], ['new', 'user.campaigns.create', 'Create campaign', 'fa-plus'], ['contacts', 'user.campaigns.contacts.index', 'Contacts & lists', 'fa-address-book']] as [$key, $route, $label, $icon])
        <a href="{{ route($route) }}" class="ui-tab {{ $active === $key ? 'is-on' : '' }}" @if($active === $key) aria-current="page" @endif><i class="fas {{ $icon }}"></i> {{ $label }}</a>
    @endforeach
</nav>
