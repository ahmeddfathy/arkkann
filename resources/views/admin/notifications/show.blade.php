@extends('layouts.app')
<head>
<link href="{{ asset('css/notifications.css') }}" rel="stylesheet">

</head>
@section('content')
<div class="container">
  <div class="card">
    <div class="card-header">
      <h4>تفاصيل قراءة الإشعار</h4>
    </div>
    <div class="card-body">
      <div class="mb-4">
        <h5>{{ $notification->data['title'] }}</h5>
        <p>{{ $notification->data['message'] }}</p>
        <div class="text-muted">
          تاريخ الإنشاء: {{ $notification->created_at->format('Y-m-d H:i') }}
        </div>
      </div>

      <div class="row mb-4">
        <div class="col-md-4">
          <div class="card bg-success text-white">
            <div class="card-body text-center">
              <h3>{{ $readCount }}</h3>
              <p class="mb-0">قرأوا الإشعار</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card bg-danger text-white">
            <div class="card-body text-center">
              <h3>{{ $unreadCount }}</h3>
              <p class="mb-0">لم يقرأوا</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card bg-info text-white">
            <div class="card-body text-center">
              <h3>{{ $totalRecipients }}</h3>
              <p class="mb-0">إجمالي المستلمين</p>
            </div>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>اسم الموظف</th>
              <th>حالة القراءة</th>
              <th>تاريخ القراءة</th>
            </tr>
          </thead>
          <tbody>
            @foreach($recipients as $recipient)
            <tr>
              <td>{{ $recipient->name }}</td>
              <td>
                @if($recipient->read_at)
                <span class="badge bg-success">تمت القراءة</span>
                @else
                <span class="badge bg-danger">لم يقرأ</span>
                @endif
              </td>
              <td>
                @if($recipient->read_at)
                {{ $recipient->read_at->format('Y-m-d H:i') }}
                @else
                -
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
