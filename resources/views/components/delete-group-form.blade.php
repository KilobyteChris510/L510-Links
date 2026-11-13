@props(['group'])

<form
    method="POST"
    action="{{ route('groups.destroy', $group) }}"
    x-data
    x-on:submit="if (! confirm('Delete this link group? Its public URL stays reserved and will not be reused.')) $event.preventDefault()"
>
    @csrf
    @method('DELETE')
    <button {{ $attributes->merge(['type' => 'submit', 'class' => 'text-sm font-medium text-red-600 hover:text-red-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300 rounded']) }}>
        {{ $slot->isEmpty() ? 'Delete' : $slot }}
    </button>
</form>
