@extends('layouts.admin.app')
@section('content')
    <section>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="w-100 text-right">
                        <a href="{{ route('admin.faq.index') }}" class="btn btn-success">
                            Back
                        </a>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <form action="{{ $url }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if (in_array($method, ['PUT', 'PATCH', 'DELETE']))
                            @method($method)
                        @endif

                        <div class="row">
                            <!-- FAQ field -->
                            <div class="col-md-12 mb-4">
                                <label for="faq" class="form-label">Enter FAQ</label>
                                <input type="text" name="faq" id="faq" class="form-control"
                                    value="{{ old('faq', $faq['faq'] ?? '') }}" required>
                            </div>

                            <!-- Explain field -->
                            <div class="col-md-12 mb-4">
                                <label for="explain" class="form-label">Enter Explain</label>
                                <textarea name="explain" id="explain" class="form-control" rows="5">{{ old('explain', $faq['explain'] ?? '') }}</textarea>

                                <script>
                                    CKEDITOR.replace('explain');
                                </script>
                            </div>

                            <!-- Submit button -->
                            <div class="col-md-12">
                                <input type="submit" value="Add FAQ" class="btn btn-success px-md-4">
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </section>
@endsection
