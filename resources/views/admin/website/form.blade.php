<!-- resources/views/settings/edit.blade.php -->

@extends('layouts.admin')

@section('content')
<div class="container">
    <h2>Edit Settings</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('settings.update', $setting->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="logo">Logo</label>
            <input type="text" class="form-control" id="logo" name="logo" value="{{ old('logo', $setting->logo) }}">
        </div>

        <div class="form-group">
            <label for="content">Content</label>
            <textarea class="form-control" id="content" name="content">{{ old('content', $setting->content) }}</textarea>
        </div>

        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $setting->title) }}">
        </div>

        <div class="form-group">
            <label for="meta_description">Meta Description</label>
            <input type="text" class="form-control" id="meta_description" name="meta_description" value="{{ old('meta_description', $setting->meta_description) }}">
        </div>

        <div class="form-group">
            <label for="emails">Emails</label>
            <input type="text" class="form-control" id="emails" name="emails" value="{{ old('emails', $setting->emails) }}">
        </div>

        <div class="form-group">
            <label for="contact_number">Contact Number</label>
            <input type="text" class="form-control" id="contact_number" name="contact_number" value="{{ old('contact_number', $setting->contact_number) }}">
        </div>

        <div class="form-group">
            <label for="footer_text">Footer Text</label>
            <input type="text" class="form-control" id="footer_text" name="footer_text" value="{{ old('footer_text', $setting->footer_text) }}">
        </div>

        <button type="submit" class="btn btn-primary">Update Settings</button>
    </form>
</div>
@endsection
