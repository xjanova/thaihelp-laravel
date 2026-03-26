@extends('errors.layout')

@section('code', '404')
@section('title', 'ไม่พบหน้าที่ค้นหา')
@section('bubble', 'หาไม่เจอค่ะ!')
@section('glow-color', 'rgba(59, 130, 246, 0.15)')
@section('border-color', 'rgba(59, 130, 246, 0.4)')
@section('accent-color', '#3b82f6')
@section('accent-color-light', '#60a5fa')

@section('message')
    หน้านี้อาจถูกย้ายหรือลบไปแล้วค่ะ<br>
    ลองกลับไปหน้าแผนที่หรือค้นหาใหม่นะคะ
@endsection

@section('actions')
    <a href="/" class="btn btn-primary">&#x1f5fa; กลับแผนที่</a>
    <button class="btn btn-secondary" onclick="history.back()">&#x2190; ย้อนกลับ</button>
@endsection
