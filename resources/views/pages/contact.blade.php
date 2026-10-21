@extends('layouts.app')
@section('content')
    <x-page-header eyebrow="Contact" title="Collaboration & professional enquiries" intro="For scientific collaboration, invitations to speak or teach, research partnerships and media enquiries." :breadcrumbs="['Contact' => null]" />
    <div class="container-site section">
        @include('blocks.contact_form', ['data' => ['heading' => 'Send a message', 'text' => 'Please include as much detail as you can — dates, audience and format for speaking invitations, or a short outline for research proposals.'], 'tone' => 'default', 'index' => 0, 'first' => false])
    </div>
@endsection
