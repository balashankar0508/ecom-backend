@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create product</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('products.store') }}" id="product-create-form">
        @csrf

        <div class="form-group">
            <label for="title">Title</label>
            <input id="title" name="title" class="form-control" value="{{ old('title') }}" required>
            @error('title')<small class="text-danger">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label for="slug">Slug (auto)</label>
            <input id="slug" name="slug" class="form-control" value="{{ old('slug') }}">
            @error('slug')<small class="text-danger">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" class="form-control">
                <option value="">-- choose or leave blank to auto-detect --</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted">If left blank, system will auto-assign by title (e.g. "shirt" → Fashion)</small>
            @error('category_id')<small class="text-danger">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label for="base_price">Price</label>
            <input id="base_price" name="base_price" type="number" step="0.01" class="form-control" value="{{ old('base_price', 0) }}" required>
            @error('base_price')<small class="text-danger">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control">{{ old('description') }}</textarea>
        </div>

        <button class="btn btn-primary" type="submit">Create Product</button>
    </form>
</div>
@endsection

@section('scripts')
<script src="{{ mix('js/products.js') }}"></script>
@endsection
