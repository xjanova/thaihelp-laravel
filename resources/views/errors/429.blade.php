@extends('errors.layout')

@section('code', '429')
@section('title', 'คำขอมากเกินไป')
@section('bubble', 'ช้าลงหน่อยนะคะ!')
@section('glow-color', 'rgba(249, 115, 22, 0.15)')
@section('border-color', 'rgba(249, 115, 22, 0.4)')
@section('accent-color', '#f97316')
@section('accent-color-light', '#fb923c')

@section('message')
    คุณส่งคำขอเร็วเกินไปค่ะ<br>
    กรุณารอสักครู่แล้วลองใหม่อีกครั้งนะคะ
@endsection

@section('actions')
    <button class="btn btn-primary" onclick="location.reload()">&#x21bb; ลองใหม่</button>
    <a href="/" class="btn btn-secondary">&#x1f5fa; กลับแผนที่</a>
@endsection
