@extends('layouts.app')
@section('content')
    <x-page-header eyebrow="Question bank" title="Practice mode" intro="One question at a time, with instant feedback, detailed explanations and key exam points."
                   :breadcrumbs="['Exam preparation' => route('exam.index'), 'Practice' => null]" />
    <div class="container-site py-12 lg:py-16">
        <livewire:question-practice />
    </div>
@endsection
