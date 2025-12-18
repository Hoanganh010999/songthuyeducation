<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassLessonSession;
use App\Models\ValuationForm;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class EvaluationPdfController extends Controller
{
    /**
     * Generate and save evaluation PDF
     */
    public function generatePdf(Request $request)
    {
        \Log::info('🔍 PDF Generation Request:', ['attendance_id' => $request->attendance_id]);
        
        $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
        ]);

        $attendance = Attendance::with([
            'student',
            'session.valuationForm.fields',
            'session.class'
        ])->findOrFail($request->attendance_id);

        \Log::info('✅ Attendance found:', [
            'id' => $attendance->id,
            'student_id' => $attendance->student_id,
            'has_evaluation_data' => !!$attendance->evaluation_data,
            'evaluation_data' => $attendance->evaluation_data
        ]);

        // Check if evaluation_data exists
        if (!$attendance->evaluation_data) {
            \Log::warning('⚠️ No evaluation data found for attendance: ' . $attendance->id);
            return response()->json([
                'success' => false,
                'message' => 'No evaluation data found'
            ], 400);
        }

        $evaluationData = is_string($attendance->evaluation_data) 
            ? json_decode($attendance->evaluation_data, true) 
            : $attendance->evaluation_data;

        $valuationForm = $attendance->session->valuationForm;

        // Prepare data for PDF
        $data = [
            'student' => $attendance->student,
            'session' => $attendance->session,
            'class' => $attendance->session->class,
            'valuationForm' => $valuationForm,
            'evaluationData' => $evaluationData,
            'attendance' => $attendance,
            'generatedAt' => now()->format('d/m/Y H:i')
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.student-evaluation', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'DejaVu Sans',
                'enable_php' => false,
                'dpi' => 150,
                'image_dpi' => 150,
                'defaultMediaType' => 'screen',
                'isFontSubsettingEnabled' => true
            ]);

        // Generate filename
        $filename = 'evaluations/' 
            . $attendance->student->employee_code 
            . '_session_' . $attendance->session->session_number 
            . '_' . now()->format('Ymd_His') 
            . '.pdf';

        \Log::info('📁 Saving PDF:', ['filename' => $filename]);

        // Save directly to public directory (Windows XAMPP doesn't support symlinks properly)
        $publicPath = public_path($filename);
        $directory = dirname($publicPath);
        
        // Create directory if not exists
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Delete old PDF if exists
        if ($attendance->evaluation_pdf_url) {
            $oldFilename = basename(parse_url($attendance->evaluation_pdf_url, PHP_URL_PATH));
            $oldPath = public_path('evaluations/' . $oldFilename);
            if (file_exists($oldPath)) {
                unlink($oldPath);
                \Log::info('🗑️ Deleted old PDF:', ['path' => $oldPath]);
            }
        }

        // Save PDF to public directory
        file_put_contents($publicPath, $pdf->output());

        // Get URL (APP_URL should include /public for XAMPP)
        $url = url($filename);

        \Log::info('✅ PDF saved:', ['url' => $url]);

        // Update attendance with PDF URL
        $attendance->update([
            'evaluation_pdf_url' => $url
        ]);

        \Log::info('✅ Attendance updated with PDF URL:', [
            'attendance_id' => $attendance->id,
            'pdf_url' => $url
        ]);

        return response()->json([
            'success' => true,
            'message' => 'PDF generated successfully',
            'data' => [
                'pdf_url' => $url,
                'filename' => basename($filename)
            ]
        ]);
    }

    /**
     * View PDF
     */
    public function viewPdf($attendanceId)
    {
        $attendance = Attendance::findOrFail($attendanceId);

        if (!$attendance->evaluation_pdf_url) {
            return response()->json([
                'success' => false,
                'message' => 'No PDF found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pdf_url' => $attendance->evaluation_pdf_url
            ]
        ]);
    }
}
