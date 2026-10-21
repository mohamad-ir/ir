@extends('emails.layout', ['title' => 'Test email'])
@section('body')
    <h1 style="font-family:Georgia,serif;font-size:22px;color:{{ setting('theme.primary') }};margin:0 0 12px">Email is working</h1>
    <p>This test message confirms that {{ setting('site.name') }} can send email with your current settings ({{ now()->toDayDateTimeString() }}).</p>
@endsection
@section('footer')
    <p style="margin:0">Sent from Settings › Email in the dashboard.</p>
@endsection
