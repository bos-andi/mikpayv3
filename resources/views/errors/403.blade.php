@extends('errors.layout')

@section('code', '403')
@section('title', 'Akses Ditolak')
@section('icon', 'fa-lock')
@section('iconClass', 'bg-amber-500/10 text-amber-600')
@section('message', $exception->getMessage() ?: 'Anda tidak punya akses ke halaman ini.')
