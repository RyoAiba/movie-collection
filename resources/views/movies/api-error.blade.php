@extends('layouts.app')

@section('title', '映画情報を取得できませんでした | Movie Shelf')

@section('content')
    <div class="mx-auto max-w-xl rounded-2xl border border-red-400/30 bg-red-400/10 px-6 py-12 text-center">
        <p class="text-lg font-semibold text-red-100">{{ $message }}</p>
        <p class="mt-3 text-sm text-red-200/70">通信状況をご確認のうえ、しばらくしてからもう一度お試しください。</p>
        <a href="{{ route('home') }}" class="mt-7 inline-block rounded-lg border border-white/15 px-5 py-2.5 text-sm font-medium transition hover:bg-white/10">トップへ戻る</a>
    </div>
@endsection
