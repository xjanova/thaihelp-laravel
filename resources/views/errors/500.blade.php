@extends('errors.layout')

@section('code', '500')
@section('title', 'เกิดข้อผิดพลาดภายใน')
@section('bubble', 'ขอโทษด้วยนะคะ!')
@section('glow-color', 'rgba(239, 68, 68, 0.15)')
@section('border-color', 'rgba(239, 68, 68, 0.4)')
@section('accent-color', '#ef4444')
@section('accent-color-light', '#f87171')

@section('message')
    เกิดข้อผิดพลาดที่ไม่คาดคิดค่ะ ทีมงานได้รับแจ้งแล้ว<br>
    กรุณาลองใหม่อีกครั้ง หรือกลับไปหน้าหลักนะคะ
@endsection

@section('actions')
    <button class="btn btn-primary" onclick="location.reload()">&#x21bb; ลองใหม่</button>
    <a href="/" class="btn btn-secondary">&#x1f5fa; กลับแผนที่</a>
@endsection
