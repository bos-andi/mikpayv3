@extends('errors.layout')

@section('code', '402')
@section('title', 'Panel Sedang Terkunci')
@section('icon', 'fa-crown')
@section('iconClass', 'bg-rose-500/10 text-rose-600')
@section('message', $exception->getMessage() ?: 'Langganan sudah berakhir. Hubungi admin ISP Anda untuk perpanjangan.')
