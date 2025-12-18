<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Phiếu Đánh Giá Học Viên</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
        }
        
        .header h1 {
            font-size: 20pt;
            color: #1e3a8a;
            margin-bottom: 5px;
        }
        
        .header h2 {
            font-size: 14pt;
            color: #3b82f6;
            font-weight: normal;
        }
        
        .info-section {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #1f2937;
        }
        
        .info-value {
            flex: 1;
            color: #374151;
        }
        
        .evaluation-section {
            margin-top: 25px;
        }
        
        .field-item {
            margin-bottom: 20px;
            page-break-inside: avoid;
            border-left: 3px solid #3b82f6;
            padding-left: 15px;
        }
        
        .field-title {
            font-size: 13pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }
        
        .field-description {
            font-size: 10pt;
            color: #6b7280;
            font-style: italic;
            margin-bottom: 10px;
        }
        
        .field-content {
            background-color: #f9fafb;
            padding: 12px;
            border-radius: 6px;
            margin-top: 8px;
            color: #1f2937;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .field-content ul,
        .field-content ol {
            margin-left: 20px;
            margin-top: 5px;
        }
        
        .field-content li {
            margin-bottom: 5px;
        }
        
        .field-content p {
            margin-bottom: 10px;
        }
        
        .checkbox-list {
            margin-top: 8px;
        }
        
        .checkbox-item {
            padding: 6px 0;
            border-bottom: 1px dotted #d1d5db;
        }
        
        .checkbox-item:last-child {
            border-bottom: none;
        }
        
        .checkbox-marker {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #3b82f6;
            background-color: #3b82f6;
            margin-right: 8px;
            vertical-align: middle;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            font-size: 9pt;
            color: #6b7280;
        }
        
        .scores-section {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding: 10px;
            background-color: #eff6ff;
            border-radius: 8px;
        }
        
        .score-item {
            text-align: center;
            flex: 1;
        }
        
        .score-label {
            font-size: 9pt;
            color: #6b7280;
            margin-bottom: 3px;
        }
        
        .score-value {
            font-size: 16pt;
            font-weight: bold;
            color: #1e40af;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>📋 PHIẾU ĐÁNH GIÁ HỌC VIÊN</h1>
        <h2>{{ $valuationForm->name }}</h2>
    </div>

    <!-- Student & Class Info -->
    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Họ tên học viên:</div>
            <div class="info-value">{{ $student->name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Mã học viên:</div>
            <div class="info-value">{{ $student->employee_code }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Lớp:</div>
            <div class="info-value">{{ $class->name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Buổi học:</div>
            <div class="info-value">Buổi {{ $session->session_number }} - {{ $session->lesson_title }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Ngày học:</div>
            <div class="info-value">{{ \Carbon\Carbon::parse($session->scheduled_date)->format('d/m/Y') }}</div>
        </div>
    </div>

    <!-- Scores Section -->
    @if($attendance->homework_score || $attendance->participation_score)
    <div class="scores-section">
        @if($attendance->homework_score)
        <div class="score-item">
            <div class="score-label">Điểm bài tập</div>
            <div class="score-value">{{ $attendance->homework_score }}/10</div>
        </div>
        @endif
        
        @if($attendance->participation_score)
        <div class="score-item">
            <div class="score-label">Mức độ tương tác</div>
            <div class="score-value">
                @if($attendance->participation_score == 5) Rất tốt
                @elseif($attendance->participation_score == 4) Tốt
                @elseif($attendance->participation_score == 3) Trung bình
                @elseif($attendance->participation_score == 2) Cần nhắc nhở
                @else Cần cải thiện
                @endif
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Evaluation Fields -->
    <div class="evaluation-section">
        @foreach($valuationForm->fields as $field)
            @php
                $fieldId = (string)$field->id;
                $value = $evaluationData[$fieldId] ?? null;
            @endphp
            
            @if($value)
            <div class="field-item">
                <div class="field-title">{{ $field->field_title }}</div>
                
                @if($field->field_description)
                <div class="field-description">{{ $field->field_description }}</div>
                @endif
                
                <div class="field-content">
                    @if($field->field_type === 'text')
                        {!! $value !!}
                    @elseif($field->field_type === 'dropdown')
                        {{ $value }}
                    @elseif($field->field_type === 'checkbox')
                        <div class="checkbox-list">
                            @foreach((array)$value as $item)
                                <div class="checkbox-item">
                                    <span class="checkbox-marker"></span> {{ $item }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            @endif
        @endforeach
    </div>

    <!-- Comment Section -->
    @if($attendance->notes)
    <div class="field-item" style="margin-top: 30px;">
        <div class="field-title">💬 Nhận xét của giáo viên</div>
        <div class="field-content">
            {{ $attendance->notes }}
        </div>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Ngày tạo: {{ $generatedAt }}</p>
        <p style="margin-top: 5px;">Phiếu này được tạo tự động từ hệ thống quản lý học vụ</p>
    </div>
</body>
</html>

