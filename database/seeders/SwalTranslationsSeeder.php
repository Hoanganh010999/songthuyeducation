<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class SwalTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $en = Language::where('code', 'en')->first();
        $vi = Language::where('code', 'vi')->first();

        if (!$en || !$vi) {
            $this->command->error('Languages not found. Please run LanguageSeeder first.');
            return;
        }

        // Common translations for SweetAlert2
        $commonEn = [
            'success' => 'Success',
            'error' => 'Error',
            'warning' => 'Warning',
            'info' => 'Information',
            'confirm' => 'Confirm',
            'ok' => 'OK',
            'cancel' => 'Cancel',
            'delete' => 'Delete',
            'yes' => 'Yes',
            'no' => 'No',
            'loading' => 'Loading...',
            'saving' => 'Saving...',
            'confirm_delete' => 'Are you sure?',
            'confirm_delete_message' => 'This action cannot be undone!',
            'operation_success' => 'Operation completed successfully',
            'operation_failed' => 'Operation failed',
            'no_data' => 'No data available',
            'error_occurred' => 'An error occurred',
            'select' => 'Select',
            'search' => 'Search...',
            'showing' => 'Showing',
            'of' => 'of',
            'previous' => 'Previous',
            'next' => 'Next',
            'edit' => 'Edit',
            'view' => 'View',
            'actions' => 'Actions',
            'save' => 'Save',
            'close' => 'Close',
            'back' => 'Back',
            'continue' => 'Continue',
            'submit' => 'Submit',
            'reset' => 'Reset',
        ];

        $commonVi = [
            'success' => 'Thành Công',
            'error' => 'Lỗi',
            'warning' => 'Cảnh Báo',
            'info' => 'Thông Tin',
            'confirm' => 'Xác Nhận',
            'ok' => 'Đồng Ý',
            'cancel' => 'Hủy',
            'delete' => 'Xóa',
            'yes' => 'Có',
            'no' => 'Không',
            'loading' => 'Đang tải...',
            'saving' => 'Đang lưu...',
            'confirm_delete' => 'Bạn có chắc chắn?',
            'confirm_delete_message' => 'Hành động này không thể hoàn tác!',
            'operation_success' => 'Thao tác thành công',
            'operation_failed' => 'Thao tác thất bại',
            'no_data' => 'Không có dữ liệu',
            'error_occurred' => 'Đã xảy ra lỗi',
            'select' => 'Chọn',
            'search' => 'Tìm kiếm...',
            'showing' => 'Hiển thị',
            'of' => 'trên',
            'previous' => 'Trước',
            'next' => 'Sau',
            'edit' => 'Sửa',
            'view' => 'Xem',
            'actions' => 'Thao Tác',
            'save' => 'Lưu',
            'close' => 'Đóng',
            'back' => 'Quay Lại',
            'continue' => 'Tiếp Tục',
            'submit' => 'Gửi',
            'reset' => 'Đặt Lại',
        ];

        foreach ($commonEn as $key => $value) {
            Translation::updateOrCreate(
                ['language_id' => $en->id, 'group' => 'common', 'key' => $key],
                ['value' => $value]
            );
        }

        foreach ($commonVi as $key => $value) {
            Translation::updateOrCreate(
                ['language_id' => $vi->id, 'group' => 'common', 'key' => $key],
                ['value' => $value]
            );
        }

        $this->command->info('✅ SweetAlert2 translations added successfully!');
    }
}
