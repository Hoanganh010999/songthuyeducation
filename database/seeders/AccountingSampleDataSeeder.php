<?php

namespace Database\Seeders;

use App\Models\AccountCategory;
use App\Models\AccountItem;
use Illuminate\Database\Seeder;

class AccountingSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏫 Creating accounting sample data for educational institution...');

        // ============================================
        // DANH MỤC THU (INCOME CATEGORIES)
        // ============================================
        
        // 1. Thu từ Học phí
        $tuitionCategory = AccountCategory::create([
            'code' => 'THU-HP',
            'name' => 'Thu từ Học phí',
            'type' => 'income',
            'cost_type' => null, // Thu không có cost_type
            'parent_id' => null,
            'description' => 'Các khoản thu từ học phí các khóa học',
            'is_active' => true,
            'sort_order' => 1
        ]);

        // Sub-categories cho Học phí
        $tuitionSubs = [
            ['code' => 'THU-HP-IELTS', 'name' => 'Học phí IELTS', 'description' => 'Thu từ các khóa IELTS'],
            ['code' => 'THU-HP-TOEIC', 'name' => 'Học phí TOEIC', 'description' => 'Thu từ các khóa TOEIC'],
            ['code' => 'THU-HP-GIAO', 'name' => 'Học phí Giao tiếp', 'description' => 'Thu từ các khóa giao tiếp'],
            ['code' => 'THU-HP-THIEU', 'name' => 'Học phí Thiếu nhi', 'description' => 'Thu từ các khóa thiếu nhi'],
        ];

        foreach ($tuitionSubs as $sub) {
            $subCat = AccountCategory::create([
                'code' => $sub['code'],
                'name' => $sub['name'],
                'type' => 'income',
                'cost_type' => null,
                'parent_id' => $tuitionCategory->id,
                'description' => $sub['description'],
                'is_active' => true,
                'sort_order' => 1
            ]);

            // Tạo Account Items cho từng sub-category
            AccountItem::create([
                'code' => $sub['code'] . '-CK',
                'name' => $sub['name'] . ' - Chính khóa',
                'category_id' => $subCat->id,
                'type' => 'income',
                'description' => 'Thu học phí chính khóa',
                'is_active' => true,
                'sort_order' => 1
            ]);

            AccountItem::create([
                'code' => $sub['code'] . '-PK',
                'name' => $sub['name'] . ' - Phụ khóa',
                'category_id' => $subCat->id,
                'type' => 'income',
                'description' => 'Thu học phí phụ khóa/bổ trợ',
                'is_active' => true,
                'sort_order' => 2
            ]);
        }

        // 2. Thu từ Dịch vụ
        $serviceCategory = AccountCategory::create([
            'code' => 'THU-DV',
            'name' => 'Thu từ Dịch vụ',
            'type' => 'income',
            'cost_type' => null,
            'parent_id' => null,
            'description' => 'Các khoản thu từ dịch vụ',
            'is_active' => true,
            'sort_order' => 2
        ]);

        $serviceItems = [
            ['code' => 'THU-DV-TLTK', 'name' => 'Thu phí tư vấn/placement test', 'desc' => 'Phí tư vấn, kiểm tra trình độ'],
            ['code' => 'THU-DV-TAILIEU', 'name' => 'Thu bán tài liệu', 'desc' => 'Sách, giáo trình, tài liệu'],
            ['code' => 'THU-DV-THI', 'name' => 'Thu phí thi thử', 'desc' => 'Lệ phí thi thử IELTS, TOEIC'],
            ['code' => 'THU-DV-CHUNGNHAN', 'name' => 'Thu phí cấp chứng nhận', 'desc' => 'Phí cấp chứng chỉ, chứng nhận'],
        ];

        foreach ($serviceItems as $item) {
            AccountItem::create([
                'code' => $item['code'],
                'name' => $item['name'],
                'category_id' => $serviceCategory->id,
                'type' => 'income',
                'description' => $item['desc'],
                'is_active' => true,
                'sort_order' => 1
            ]);
        }

        // 3. Thu khác
        $otherIncomeCategory = AccountCategory::create([
            'code' => 'THU-KHAC',
            'name' => 'Thu khác',
            'type' => 'income',
            'cost_type' => null,
            'parent_id' => null,
            'description' => 'Các khoản thu khác',
            'is_active' => true,
            'sort_order' => 3
        ]);

        $otherIncomeItems = [
            ['code' => 'THU-KHAC-TK', 'name' => 'Lãi tiền gửi ngân hàng', 'desc' => 'Lãi từ tiền gửi'],
            ['code' => 'THU-KHAC-HT', 'name' => 'Thu từ hợp tác đối tác', 'desc' => 'Thu từ các chương trình hợp tác'],
            ['code' => 'THU-KHAC-TC', 'name' => 'Thu từ tài trợ', 'desc' => 'Tài trợ, quyên góp'],
        ];

        foreach ($otherIncomeItems as $item) {
            AccountItem::create([
                'code' => $item['code'],
                'name' => $item['name'],
                'category_id' => $otherIncomeCategory->id,
                'type' => 'income',
                'description' => $item['desc'],
                'is_active' => true,
                'sort_order' => 1
            ]);
        }

        // ============================================
        // DANH MỤC CHI (EXPENSE CATEGORIES)
        // ============================================

        // 1. Chi phí vận hành - BIẾN PHÍ
        $operatingCategory = AccountCategory::create([
            'code' => 'CHI-VH',
            'name' => 'Chi phí vận hành',
            'type' => 'expense',
            'cost_type' => 'variable',
            'parent_id' => null,
            'description' => 'Các khoản chi vận hành hàng ngày',
            'is_active' => true,
            'sort_order' => 1
        ]);

        $operatingItems = [
            ['code' => 'CHI-VH-DIEN', 'name' => 'Tiền điện', 'desc' => 'Điện năng tiêu thụ'],
            ['code' => 'CHI-VH-NUOC', 'name' => 'Tiền nước', 'desc' => 'Nước sinh hoạt'],
            ['code' => 'CHI-VH-NET', 'name' => 'Internet', 'desc' => 'Cước internet, wifi'],
            ['code' => 'CHI-VH-DIENTHOAI', 'name' => 'Điện thoại', 'desc' => 'Cước điện thoại cố định'],
            ['code' => 'CHI-VH-VESINHH', 'name' => 'Vệ sinh', 'desc' => 'Dịch vụ vệ sinh, dọn dẹp'],
            ['code' => 'CHI-VH-BAOVE', 'name' => 'Bảo vệ', 'desc' => 'Dịch vụ bảo vệ, an ninh'],
            ['code' => 'CHI-VH-SUACHUA', 'name' => 'Sửa chữa bảo trì', 'desc' => 'Sửa chữa trang thiết bị'],
        ];

        foreach ($operatingItems as $item) {
            AccountItem::create([
                'code' => $item['code'],
                'name' => $item['name'],
                'category_id' => $operatingCategory->id,
                'type' => 'expense',
                'description' => $item['desc'],
                'is_active' => true,
                'sort_order' => 1
            ]);
        }

        // 2. Chi phí lương văn phòng - ĐỊNH PHÍ
        $officeSalaryCategory = AccountCategory::create([
            'code' => 'CHI-LVP',
            'name' => 'Chi phí lương văn phòng',
            'type' => 'expense',
            'cost_type' => 'fixed',
            'parent_id' => null,
            'description' => 'Lương và phúc lợi nhân viên văn phòng',
            'is_active' => true,
            'sort_order' => 2
        ]);

        $officeSalaryItems = [
            ['code' => 'CHI-LVP-LUONG', 'name' => 'Lương nhân viên văn phòng', 'desc' => 'Lương cơ bản NV hành chính'],
            ['code' => 'CHI-LVP-BHXH', 'name' => 'BHXH, BHYT, BHTN văn phòng', 'desc' => 'Bảo hiểm bắt buộc NV văn phòng'],
            ['code' => 'CHI-LVP-THUONG', 'name' => 'Thưởng nhân viên văn phòng', 'desc' => 'Thưởng hiệu suất, KPI'],
            ['code' => 'CHI-LVP-PHUCAP', 'name' => 'Phụ cấp nhân viên văn phòng', 'desc' => 'Ăn trưa, xăng xe, điện thoại...'],
        ];

        foreach ($officeSalaryItems as $item) {
            AccountItem::create([
                'code' => $item['code'],
                'name' => $item['name'],
                'category_id' => $officeSalaryCategory->id,
                'type' => 'expense',
                'description' => $item['desc'],
                'is_active' => true,
                'sort_order' => 1
            ]);
        }

        // 3. Chi phí lương giáo viên - BIẾN PHÍ
        $teacherSalaryCategory = AccountCategory::create([
            'code' => 'CHI-LGV',
            'name' => 'Chi phí lương giáo viên',
            'type' => 'expense',
            'cost_type' => 'variable',
            'parent_id' => null,
            'description' => 'Lương và phúc lợi giáo viên',
            'is_active' => true,
            'sort_order' => 3
        ]);

        $teacherSalaryItems = [
            ['code' => 'CHI-LGV-LUONG', 'name' => 'Lương giáo viên', 'desc' => 'Lương cơ bản, phụ cấp giáo viên'],
            ['code' => 'CHI-LGV-BHXH', 'name' => 'BHXH, BHYT, BHTN giáo viên', 'desc' => 'Bảo hiểm bắt buộc GV'],
            ['code' => 'CHI-LGV-THUONG', 'name' => 'Thưởng giáo viên', 'desc' => 'Thưởng theo KPI, đánh giá học viên'],
            ['code' => 'CHI-LGV-DAOTAO', 'name' => 'Đào tạo giáo viên', 'desc' => 'Khóa học nâng cao, workshop, chứng chỉ'],
        ];

        foreach ($teacherSalaryItems as $item) {
            AccountItem::create([
                'code' => $item['code'],
                'name' => $item['name'],
                'category_id' => $teacherSalaryCategory->id,
                'type' => 'expense',
                'description' => $item['desc'],
                'is_active' => true,
                'sort_order' => 1
            ]);
        }

        // 4. Chi phí văn phòng phẩm - BIẾN PHÍ
        $officeSuppliesCategory = AccountCategory::create([
            'code' => 'CHI-VPP',
            'name' => 'Chi phí văn phòng phẩm',
            'type' => 'expense',
            'cost_type' => 'variable',
            'parent_id' => null,
            'description' => 'Văn phòng phẩm cho hoạt động hành chính',
            'is_active' => true,
            'sort_order' => 4
        ]);

        $officeSuppliesItems = [
            ['code' => 'CHI-VPP-GIAYBUT', 'name' => 'Giấy, bút, văn phòng phẩm', 'desc' => 'Giấy A4, bút, kẹp, ghim...'],
            ['code' => 'CHI-VPP-IN', 'name' => 'Mực in, vật tư máy in', 'desc' => 'Mực máy in, toner...'],
            ['code' => 'CHI-VPP-PHOTOCOPY', 'name' => 'Photocopy, in ấn', 'desc' => 'Dịch vụ photocopy, in tài liệu hành chính'],
        ];

        foreach ($officeSuppliesItems as $item) {
            AccountItem::create([
                'code' => $item['code'],
                'name' => $item['name'],
                'category_id' => $officeSuppliesCategory->id,
                'type' => 'expense',
                'description' => $item['desc'],
                'is_active' => true,
                'sort_order' => 1
            ]);
        }

        // 5. Vật tư lớp học - BIẾN PHÍ
        $classroomSuppliesCategory = AccountCategory::create([
            'code' => 'CHI-VTLH',
            'name' => 'Vật tư lớp học',
            'type' => 'expense',
            'cost_type' => 'variable',
            'parent_id' => null,
            'description' => 'Vật tư, trang thiết bị phục vụ giảng dạy',
            'is_active' => true,
            'sort_order' => 5
        ]);

        $classroomSuppliesItems = [
            ['code' => 'CHI-VTLH-TAILIEU', 'name' => 'Mua tài liệu giảng dạy', 'desc' => 'Sách giáo khoa, tài liệu tham khảo cho GV'],
            ['code' => 'CHI-VTLH-BANPHAN', 'name' => 'Bảng, phấn, bút lông', 'desc' => 'Phấn viết bảng, bút lông, tẩy bảng'],
            ['code' => 'CHI-VTLH-THIETBI', 'name' => 'Thiết bị giảng dạy', 'desc' => 'Loa, mic, remote máy chiếu...'],
            ['code' => 'CHI-VTLH-PHOTOCOPY', 'name' => 'Photocopy tài liệu học viên', 'desc' => 'In ấn, photocopy handouts, bài tập'],
        ];

        foreach ($classroomSuppliesItems as $item) {
            AccountItem::create([
                'code' => $item['code'],
                'name' => $item['name'],
                'category_id' => $classroomSuppliesCategory->id,
                'type' => 'expense',
                'description' => $item['desc'],
                'is_active' => true,
                'sort_order' => 1
            ]);
        }

        // 6. Chi phí bán hàng (Marketing & Sales) - BIẾN PHÍ
        $salesCategory = AccountCategory::create([
            'code' => 'CHI-BH',
            'name' => 'Chi phí bán hàng',
            'type' => 'expense',
            'cost_type' => 'variable',
            'parent_id' => null,
            'description' => 'Chi phí marketing, quảng cáo, chăm sóc khách hàng',
            'is_active' => true,
            'sort_order' => 6
        ]);

        $salesItems = [
            ['code' => 'CHI-BH-FB', 'name' => 'Quảng cáo Facebook', 'desc' => 'Facebook Ads'],
            ['code' => 'CHI-BH-GOOGLE', 'name' => 'Quảng cáo Google', 'desc' => 'Google Ads, SEO'],
            ['code' => 'CHI-BH-BANNER', 'name' => 'In banner, standee, poster', 'desc' => 'Vật phẩm truyền thông'],
            ['code' => 'CHI-BH-EVENT', 'name' => 'Tổ chức sự kiện', 'desc' => 'Hội thảo, workshop, offline event'],
            ['code' => 'CHI-BH-GIFT', 'name' => 'Quà tặng khách hàng', 'desc' => 'Quà tri ân, khuyến mãi, voucher'],
            ['code' => 'CHI-BH-HOAHONG', 'name' => 'Hoa hồng tư vấn viên', 'desc' => 'Hoa hồng sales, telesales'],
        ];

        foreach ($salesItems as $item) {
            AccountItem::create([
                'code' => $item['code'],
                'name' => $item['name'],
                'category_id' => $salesCategory->id,
                'type' => 'expense',
                'description' => $item['desc'],
                'is_active' => true,
                'sort_order' => 1
            ]);
        }

        // ============================================
        // ĐẦU TƯ CƠ SỞ VẬT CHẤT
        // ============================================

        // 7. Đầu tư CSVC - INFRASTRUCTURE
        $infrastructureCategory = AccountCategory::create([
            'code' => 'CHI-CSVC',
            'name' => 'Đầu tư cơ sở vật chất',
            'type' => 'expense',
            'cost_type' => 'infrastructure',
            'parent_id' => null,
            'description' => 'Đầu tư mua sắm tài sản, nâng cấp cơ sở vật chất',
            'is_active' => true,
            'sort_order' => 7
        ]);

        $infrastructureItems = [
            ['code' => 'CHI-CSVC-THUE', 'name' => 'Thuê/mua mặt bằng', 'desc' => 'Tiền thuê văn phòng, lớp học dài hạn'],
            ['code' => 'CHI-CSVC-NOITHAT', 'name' => 'Mua nội thất', 'desc' => 'Bàn ghế, tủ, kệ sách...'],
            ['code' => 'CHI-CSVC-MAYCHIEU', 'name' => 'Máy chiếu, tivi, bảng thông minh', 'desc' => 'Thiết bị giảng dạy cao cấp'],
            ['code' => 'CHI-CSVC-DIEUHOA', 'name' => 'Điều hòa, máy lạnh', 'desc' => 'Hệ thống điều hòa không khí'],
            ['code' => 'CHI-CSVC-MAYTINHH', 'name' => 'Máy tính, laptop', 'desc' => 'Máy tính cho GV, nhân viên'],
            ['code' => 'CHI-CSVC-PHANMEM', 'name' => 'Phần mềm, hệ thống quản lý', 'desc' => 'CRM, ERP, LMS, license phần mềm'],
            ['code' => 'CHI-CSVC-XAYDUNG', 'name' => 'Xây dựng, sửa chữa lớn', 'desc' => 'Nâng cấp cơ sở vật chất lớn'],
        ];

        foreach ($infrastructureItems as $item) {
            AccountItem::create([
                'code' => $item['code'],
                'name' => $item['name'],
                'category_id' => $infrastructureCategory->id,
                'type' => 'expense',
                'description' => $item['desc'],
                'is_active' => true,
                'sort_order' => 1
            ]);
        }

        $this->command->info('✅ Created ' . AccountCategory::count() . ' categories and ' . AccountItem::count() . ' account items!');
        $this->command->info('📊 Structure:');
        $this->command->info('   - 3 THU categories (income) + 15 items');
        $this->command->info('   - 7 CHI categories (expense):');
        $this->command->info('     • 1 Định phí (fixed): Lương văn phòng');
        $this->command->info('     • 5 Biến phí (variable): Vận hành, Lương GV, VPP, Vật tư lớp, Bán hàng');
        $this->command->info('     • 1 Đầu tư CSVC (infrastructure)');
        $this->command->info('   - Total: ' . AccountItem::count() . ' account items');
    }
}
