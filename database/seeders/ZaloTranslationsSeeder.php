<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ZaloTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translations = [
            // Common buttons
            'add' => ['en' => 'Add', 'vi' => 'Thêm'],
            'create' => ['en' => 'Create', 'vi' => 'Tạo'],
            'cancel' => ['en' => 'Cancel', 'vi' => 'Hủy'],
            'delete' => ['en' => 'Delete', 'vi' => 'Xóa'],
            'confirm' => ['en' => 'Confirm', 'vi' => 'Xác nhận'],
            'save' => ['en' => 'Save', 'vi' => 'Lưu'],
            'change' => ['en' => 'Change', 'vi' => 'Thay đổi'],
            'assign' => ['en' => 'Assign', 'vi' => 'Gán'],
            'remove' => ['en' => 'Remove', 'vi' => 'Xóa'],
            'close' => ['en' => 'Close', 'vi' => 'Đóng'],

            // Status labels
            'primary' => ['en' => 'Primary', 'vi' => 'Chính'],
            'connected' => ['en' => 'Connected', 'vi' => 'Đã kết nối'],
            'loading' => ['en' => 'Loading', 'vi' => 'Đang tải'],
            'uploading' => ['en' => 'Uploading', 'vi' => 'Đang tải lên'],
            'sending' => ['en' => 'Sending', 'vi' => 'Đang gửi'],
            'creating' => ['en' => 'Creating', 'vi' => 'Đang tạo'],
            'success' => ['en' => 'Success', 'vi' => 'Thành công'],
            'error' => ['en' => 'Error', 'vi' => 'Lỗi'],
            'failed' => ['en' => 'Failed', 'vi' => 'Thất bại'],

            // Friend management
            'add_friend' => ['en' => 'Add Friend', 'vi' => 'Thêm bạn'],
            'add_friend_button' => ['en' => 'Friend Request', 'vi' => 'Kết bạn'],
            'phone_number' => ['en' => 'Phone Number', 'vi' => 'Số điện thoại'],
            'phone_placeholder' => ['en' => 'Enter phone number', 'vi' => 'Nhập số điện thoại'],
            'phone_example' => ['en' => 'E.g: 0928596026', 'vi' => 'VD: 0928596026'],
            'searching' => ['en' => 'Searching...', 'vi' => 'Đang tìm kiếm...'],
            'recent_results' => ['en' => 'Recent Results', 'vi' => 'Kết quả gần nhất'],
            'suggested_friends' => ['en' => 'Suggested Friends', 'vi' => 'Có thể bạn quen'],
            'user_not_found' => ['en' => 'User not found', 'vi' => 'Không tìm thấy người dùng'],
            'check_phone_number' => ['en' => 'Please check the phone number', 'vi' => 'Vui lòng kiểm tra lại số điện thoại'],
            'enter_phone_to_search' => ['en' => 'Enter phone number to search', 'vi' => 'Nhập số điện thoại để tìm kiếm'],
            'search_friends_instruction' => ['en' => 'Enter phone number to search for friends', 'vi' => 'Nhập số điện thoại để tìm kiếm bạn bè'],
            'friend_request_sent' => ['en' => 'Friend request sent!', 'vi' => 'Đã gửi lời mời!'],
            'friend_request_sent_to' => ['en' => 'Friend request has been sent to {name}', 'vi' => 'Lời mời kết bạn đã được gửi đến {name}'],
            'friend_request_failed' => ['en' => 'Friend request failed', 'vi' => 'Gửi lời mời thất bại'],
            'cannot_send_friend_request' => ['en' => 'Cannot send friend request', 'vi' => 'Không thể gửi lời mời kết bạn'],

            // Group management
            'create_group' => ['en' => 'Create Group', 'vi' => 'Tạo nhóm'],
            'create_new_group' => ['en' => 'Create New Group', 'vi' => 'Tạo nhóm mới'],
            'group_name' => ['en' => 'Group Name', 'vi' => 'Tên nhóm'],
            'group_name_optional' => ['en' => 'Group Name (Optional)', 'vi' => 'Tên nhóm (Tùy chọn)'],
            'enter_group_name' => ['en' => 'Enter group name', 'vi' => 'Nhập tên nhóm'],
            'select_members' => ['en' => 'Select Members', 'vi' => 'Chọn thành viên'],
            'select_members_to_add' => ['en' => 'Select Members to Add', 'vi' => 'Chọn thành viên để thêm'],
            'search_friends' => ['en' => 'Search friends...', 'vi' => 'Tìm kiếm bạn bè...'],
            'no_friends_available' => ['en' => 'No friends available. Add friends first!', 'vi' => 'Không có bạn bè. Hãy thêm bạn trước!'],
            'no_friends_to_add' => ['en' => 'No friends available to add.', 'vi' => 'Không có bạn bè nào để thêm.'],
            'loading_friends' => ['en' => 'Loading friends...', 'vi' => 'Đang tải danh sách bạn bè...'],
            'no_members_selected' => ['en' => 'No Members Selected', 'vi' => 'Chưa chọn thành viên'],
            'select_at_least_one' => ['en' => 'Please select at least one member for the group.', 'vi' => 'Vui lòng chọn ít nhất một thành viên cho nhóm.'],
            'group_created' => ['en' => 'Group Created!', 'vi' => 'Đã tạo nhóm!'],
            'group_created_with_members' => ['en' => 'Successfully created group with {count} members', 'vi' => 'Đã tạo nhóm thành công với {count} thành viên'],
            'add_members_to_group' => ['en' => 'Add Members to Group', 'vi' => 'Thêm thành viên vào nhóm'],
            'add_to_group' => ['en' => 'Add to Group', 'vi' => 'Thêm vào nhóm'],
            'adding' => ['en' => 'Adding...', 'vi' => 'Đang thêm...'],
            'already_in_group' => ['en' => '(Already in group)', 'vi' => '(Đã trong nhóm)'],

            // Group avatar
            'change_group_avatar' => ['en' => 'Change Group Avatar', 'vi' => 'Thay đổi ảnh đại diện nhóm'],
            'change_avatar_confirm' => ['en' => 'Change Group Avatar?', 'vi' => 'Thay đổi ảnh đại diện nhóm?'],
            'change_avatar_confirm_text' => ['en' => 'Do you want to change the group avatar?', 'vi' => 'Bạn có muốn thay đổi ảnh đại diện nhóm?'],
            'yes_change_it' => ['en' => 'Yes, Change It', 'vi' => 'Đồng ý, thay đổi'],
            'uploading_avatar' => ['en' => 'Uploading avatar, please wait...', 'vi' => 'Đang tải ảnh lên, vui lòng đợi...'],
            'avatar_changed_success' => ['en' => 'Group avatar changed successfully', 'vi' => 'Đã thay đổi ảnh đại diện nhóm thành công'],
            'invalid_file' => ['en' => 'Invalid File', 'vi' => 'File không hợp lệ'],
            'select_image_file' => ['en' => 'Please select an image file', 'vi' => 'Vui lòng chọn file ảnh'],
            'file_too_large' => ['en' => 'File Too Large', 'vi' => 'File quá lớn'],
            'image_size_limit' => ['en' => 'Image size must be less than 5MB', 'vi' => 'Kích thước ảnh phải nhỏ hơn 5MB'],

            // Account management
            'select_account' => ['en' => 'Select an account', 'vi' => 'Chọn tài khoản'],
            'select_account_instruction' => ['en' => 'Choose an account from the list to view details', 'vi' => 'Chọn một tài khoản từ danh sách để xem chi tiết'],
            'no_active_account' => ['en' => 'No Active Account', 'vi' => 'Chưa chọn tài khoản'],
            'select_account_first' => ['en' => 'Please select an active Zalo account first.', 'vi' => 'Vui lòng chọn tài khoản Zalo trước.'],
            'set_primary_account' => ['en' => 'Set Primary Account', 'vi' => 'Đặt làm tài khoản chính'],
            'set_as_primary' => ['en' => 'Set as Primary', 'vi' => 'Đặt làm chính'],
            'account_selected' => ['en' => 'Account selected: {name}', 'vi' => 'Đã chọn tài khoản: {name}'],
            'setup_primary_account' => ['en' => 'Setup Primary Account', 'vi' => 'Thiết lập tài khoản chính'],
            'setup_primary_confirm' => ['en' => 'Do you want to set this account as primary?', 'vi' => 'Bạn có muốn đặt tài khoản này làm tài khoản chính?'],
            'agree' => ['en' => 'Agree', 'vi' => 'Đồng ý'],
            'primary_account_set' => ['en' => 'Primary account has been set', 'vi' => 'Đã thiết lập tài khoản chính'],
            'cannot_set_primary' => ['en' => 'Cannot set primary account', 'vi' => 'Không thể thiết lập tài khoản chính'],
            'generating_qr' => ['en' => 'Generating QR code...', 'vi' => 'Đang tạo mã QR...'],
            'scan_qr_code' => ['en' => 'Scan QR Code', 'vi' => 'Quét mã QR'],
            'qr_generation_failed' => ['en' => 'Failed to generate QR code', 'vi' => 'Không thể tạo mã QR'],

            // Sync progress
            'syncing_data' => ['en' => 'Syncing data...', 'vi' => 'Đang đồng bộ dữ liệu...'],
            'syncing_friends' => ['en' => 'Syncing friends...', 'vi' => 'Đang đồng bộ bạn bè...'],
            'syncing_groups' => ['en' => 'Syncing groups...', 'vi' => 'Đang đồng bộ nhóm...'],
            'total_progress' => ['en' => 'Total Progress', 'vi' => 'Tổng tiến trình'],
            'login_successful' => ['en' => 'Login Successful', 'vi' => 'Đăng nhập thành công'],
            'synced_data' => ['en' => 'Synced {friends} friends and {groups} groups', 'vi' => 'Đã đồng bộ {friends} bạn bè và {groups} nhóm'],
            'sync_in_background' => ['en' => 'Account logged in. Data sync is running in the background.', 'vi' => 'Tài khoản đã đăng nhập. Đồng bộ dữ liệu đang chạy trong nền.'],
            'cannot_track_sync' => ['en' => 'Account logged in but cannot track sync progress.', 'vi' => 'Tài khoản đã đăng nhập nhưng không thể theo dõi tiến trình đồng bộ.'],
            'expected_account' => ['en' => 'Expected account:', 'vi' => 'Tài khoản mong đợi:'],
            'used_account' => ['en' => 'Used account:', 'vi' => 'Tài khoản đã dùng:'],

            // Conversation assignment
            'conversation_assignment' => ['en' => 'Conversation Assignment', 'vi' => 'Phân công cuộc hội thoại'],
            'department' => ['en' => 'Department:', 'vi' => 'Phòng ban:'],
            'not_assigned_department' => ['en' => 'Not assigned to department', 'vi' => 'Chưa gán phòng ban'],
            'unassigned' => ['en' => 'Unassigned', 'vi' => 'Chưa gán'],
            'global' => ['en' => 'Global', 'vi' => 'Toàn cục'],
            'visible_to_all_branches' => ['en' => 'Visible to all branches', 'vi' => 'Hiển thị cho tất cả chi nhánh'],
            'branch' => ['en' => 'Branch', 'vi' => 'Chi nhánh'],
            'assign_to_branch' => ['en' => 'Assign to Branch', 'vi' => 'Gán cho chi nhánh'],
            'assign_conversation' => ['en' => 'Assign Conversation', 'vi' => 'Gán cuộc hội thoại'],
            'group_assignment' => ['en' => 'Group Assignment', 'vi' => 'Gán nhóm'],
            'assign_employee' => ['en' => 'Assign Employee', 'vi' => 'Gán nhân viên'],
            'not_assigned_employee' => ['en' => 'No employee assigned', 'vi' => 'Chưa gán nhân viên'],
            'cannot_load_branches' => ['en' => 'Cannot load branches', 'vi' => 'Không thể tải danh sách chi nhánh'],
            'no_branches' => ['en' => 'No branches found', 'vi' => 'Không tìm thấy chi nhánh'],
            'branch_assigned_success' => ['en' => 'Branch assigned successfully', 'vi' => 'Đã gán chi nhánh thành công'],
            'cannot_assign_branch' => ['en' => 'Cannot assign branch', 'vi' => 'Không thể gán chi nhánh'],
            'group_assigned_success' => ['en' => 'Group assigned successfully', 'vi' => 'Đã gán nhóm thành công'],
            'account' => ['en' => 'Account', 'vi' => 'Tài khoản'],
            'department_assigned_success' => ['en' => 'Department assigned successfully', 'vi' => 'Đã gán phòng ban thành công'],
            'employee_assigned_success' => ['en' => 'Employee assigned successfully', 'vi' => 'Đã gán nhân viên thành công'],
            'assignment_removed_success' => ['en' => 'Assignment removed successfully', 'vi' => 'Đã xóa phân công thành công'],
            'assigned_employees' => ['en' => 'Assigned Employees:', 'vi' => 'Nhân viên phụ trách:'],
            'unassign_all' => ['en' => 'Unassign All', 'vi' => 'Bỏ gán tất cả'],
            'remove_all_assignments_confirm' => ['en' => 'Are you sure you want to remove all employee assignments?', 'vi' => 'Bạn có chắc muốn bỏ gán tất cả nhân viên?'],
            'assign_employees' => ['en' => 'Assign Employees', 'vi' => 'Gán nhân viên'],
            'not_assigned_employees' => ['en' => 'No employees assigned', 'vi' => 'Chưa gán nhân viên'],
            'assign_department' => ['en' => 'Assign Department', 'vi' => 'Gán phòng ban'],
            'no_departments' => ['en' => 'No departments available', 'vi' => 'Không có phòng ban nào'],
            'search_employees' => ['en' => 'Search employees...', 'vi' => 'Tìm kiếm nhân viên...'],
            'no_employees' => ['en' => 'No employees available', 'vi' => 'Không có nhân viên nào'],
            'cannot_load_departments' => ['en' => 'Cannot load departments list', 'vi' => 'Không thể tải danh sách phòng ban'],
            'cannot_load_employees' => ['en' => 'Cannot load employees list', 'vi' => 'Không thể tải danh sách nhân viên'],
            'department_assigned' => ['en' => 'Department assigned to conversation', 'vi' => 'Đã gán phòng ban cho cuộc hội thoại'],
            'cannot_assign_department' => ['en' => 'Cannot assign department', 'vi' => 'Không thể gán phòng ban'],
            'employee_already_assigned' => ['en' => 'This employee is already assigned to the conversation', 'vi' => 'Nhân viên này đã được gán cho cuộc hội thoại'],
            'notification' => ['en' => 'Notification', 'vi' => 'Thông báo'],
            'employee_assigned' => ['en' => 'Employee assigned to conversation', 'vi' => 'Đã gán nhân viên cho cuộc hội thoại'],
            'cannot_assign_employee' => ['en' => 'Cannot assign employee', 'vi' => 'Không thể gán nhân viên'],
            'remove_assignment_confirm' => ['en' => 'Are you sure you want to remove this assignment?', 'vi' => 'Bạn có chắc muốn xóa phân công này?'],
            'assignment_removed' => ['en' => 'Employee assignment removed', 'vi' => 'Đã xóa phân công nhân viên'],
            'cannot_remove_assignment' => ['en' => 'Cannot remove assignment', 'vi' => 'Không thể xóa phân công'],
            'remove_assignment' => ['en' => 'Remove assignment', 'vi' => 'Xóa phân công'],

            // Chat / Messages
            'refresh_messages' => ['en' => 'Refresh messages', 'vi' => 'Đồng bộ lại tin nhắn'],
            'document' => ['en' => 'Document', 'vi' => 'Tài liệu'],
            'download' => ['en' => 'Download', 'vi' => 'Tải xuống'],
            'sticker' => ['en' => 'Sticker', 'vi' => 'Sticker'],
            'open_link' => ['en' => 'Open link', 'vi' => 'Mở liên kết'],
            'reaction_tooltip' => ['en' => '{count} person reacted {reaction}', 'vi' => '{count} người đã {reaction}'],
            'reply' => ['en' => 'Reply', 'vi' => 'Trả lời'],
            'react' => ['en' => 'React', 'vi' => 'Thả cảm xúc'],
            'replying_to' => ['en' => 'Replying to', 'vi' => 'Đang trả lời'],
            'type_reply' => ['en' => 'Type your reply...', 'vi' => 'Nhập câu trả lời...'],
            'type_message' => ['en' => 'Type a message...', 'vi' => 'Nhập tin nhắn...'],
            'image_placeholder' => ['en' => '📷 Image', 'vi' => '📷 Hình ảnh'],
            'search_error' => ['en' => 'Search Error', 'vi' => 'Lỗi tìm kiếm'],
            'cannot_search_users' => ['en' => 'Cannot search users', 'vi' => 'Không thể tìm kiếm người dùng'],
            'go_to_account_manager' => ['en' => 'Go to Account Manager', 'vi' => 'Đi tới Quản lý tài khoản'],

            // Rich text editor formatting
            'bold' => ['en' => 'Bold', 'vi' => 'Đậm'],
            'italic' => ['en' => 'Italic', 'vi' => 'Nghiêng'],
            'underline' => ['en' => 'Underline', 'vi' => 'Gạch chân'],
            'bullet_list' => ['en' => 'Bullet List', 'vi' => 'Danh sách'],
            'red' => ['en' => 'Red', 'vi' => 'Đỏ'],
            'orange' => ['en' => 'Orange', 'vi' => 'Cam'],
            'yellow' => ['en' => 'Yellow', 'vi' => 'Vàng'],
            'green' => ['en' => 'Green', 'vi' => 'Xanh lá'],
            'image' => ['en' => 'Image', 'vi' => 'Hình ảnh'],
            'image_sent' => ['en' => 'Image sent successfully', 'vi' => 'Đã gửi hình ảnh thành công'],

            // Message status
            'sent' => ['en' => 'Sent', 'vi' => 'Đã gửi'],
            'delivered' => ['en' => 'Delivered', 'vi' => 'Đã nhận'],
            'seen' => ['en' => 'Seen', 'vi' => 'Đã xem'],

            // Bulk send
            'characters' => ['en' => 'characters', 'vi' => 'ký tự'],
            'people' => ['en' => 'people', 'vi' => 'người'],

            // Message templates
            'template_homework_new' => ['en' => '📚 New homework has been assigned. Please check the system and submit on time.', 'vi' => '📚 Bài tập mới đã được giao. Vui lòng kiểm tra hệ thống và nộp bài đúng hạn.'],
            'template_homework_reminder' => ['en' => '⏰ Reminder: Homework deadline is approaching. Please complete and submit soon.', 'vi' => '⏰ Nhắc nhở: Bài tập sắp hết hạn. Vui lòng hoàn thành và nộp bài sớm nhất.'],
            'template_class_cancelled' => ['en' => '⚠️ Notice: Today\'s class is temporarily cancelled. Makeup schedule will be announced later.', 'vi' => '⚠️ Thông báo: Lớp học hôm nay tạm nghỉ. Lịch học bù sẽ được thông báo sau.'],
            'template_test_congratulations' => ['en' => '🎉 Congratulations! You have completed the test excellently.', 'vi' => '🎉 Chúc mừng! Bạn đã hoàn thành xuất sắc bài kiểm tra.'],

            // Customer Zalo Chat Modal
            'chat' => ['en' => 'Chat', 'vi' => 'Trò chuyện'],
            'checking_account' => ['en' => 'Checking account', 'vi' => 'Đang kiểm tra tài khoản'],
            'no_zalo_account' => ['en' => 'No Zalo account', 'vi' => 'Không có tài khoản Zalo'],
            'customer_no_zalo_hint' => ['en' => 'This customer does not have a Zalo account associated with this phone number.', 'vi' => 'Khách hàng này không có tài khoản Zalo liên kết với số điện thoại này.'],
            'friend' => ['en' => 'Friend', 'vi' => 'Bạn bè'],
            'send_friend_request' => ['en' => 'Send Friend Request', 'vi' => 'Gửi lời mời kết bạn'],
            'not_friend_yet' => ['en' => 'Not friend yet', 'vi' => 'Chưa kết bạn'],
            'send_friend_request_hint' => ['en' => 'Send a friend request to start chatting with this customer.', 'vi' => 'Gửi lời mời kết bạn để bắt đầu trò chuyện với khách hàng này.'],
            'no_messages' => ['en' => 'No messages yet', 'vi' => 'Chưa có tin nhắn nào'],
            'start_conversation_hint' => ['en' => 'Send a message to start the conversation', 'vi' => 'Gửi tin nhắn để bắt đầu cuộc trò chuyện'],
            'will_create_conversation' => ['en' => 'Sending will create a new conversation', 'vi' => 'Gửi tin nhắn sẽ tạo cuộc hội thoại mới'],
            'customer_no_phone' => ['en' => 'Customer has no phone number', 'vi' => 'Khách hàng chưa có số điện thoại'],
            'message_sent' => ['en' => 'Message sent successfully', 'vi' => 'Đã gửi tin nhắn thành công'],

            // Upload and media
            'upload_file_or_folder' => ['en' => 'File/Folder', 'vi' => 'File/Thư mục'],
            'upload_file' => ['en' => 'Upload File', 'vi' => 'Tải lên file'],
            'upload_folder' => ['en' => 'Upload Folder', 'vi' => 'Tải lên thư mục'],
            'upload_image' => ['en' => 'Image', 'vi' => 'Hình ảnh'],
            'upload_video' => ['en' => 'Video', 'vi' => 'Video'],
            'upload_audio' => ['en' => 'Audio', 'vi' => 'Audio'],
            'upload_audio_file' => ['en' => 'Upload Audio File', 'vi' => 'Tải lên file âm thanh'],
            'record_audio' => ['en' => 'Record Audio', 'vi' => 'Ghi âm trực tiếp'],
            'rich_text_editor' => ['en' => 'Rich Text', 'vi' => 'Văn bản'],
            'create_event' => ['en' => 'Event', 'vi' => 'Sự kiện'],
            'add_members' => ['en' => 'Add Members', 'vi' => 'Thêm thành viên'],
            'double_click_to_chat' => ['en' => 'Double click to chat', 'vi' => 'Nhấp đúp để trò chuyện'],
            'click_to_download' => ['en' => 'Click to download', 'vi' => 'Nhấp để tải xuống'],
        ];

        // Get language IDs
        $englishId = DB::table('languages')->where('code', 'en')->value('id');
        $vietnameseId = DB::table('languages')->where('code', 'vi')->value('id');

        if (!$englishId || !$vietnameseId) {
            $this->command->error('Languages not found in database!');
            return;
        }

        $inserted = 0;
        $updated = 0;

        foreach ($translations as $key => $values) {
            // Insert English translation
            $existingEn = DB::table('translations')
                ->where('language_id', $englishId)
                ->where('group', 'zalo')
                ->where('key', $key)
                ->first();

            if ($existingEn) {
                DB::table('translations')
                    ->where('id', $existingEn->id)
                    ->update([
                        'value' => $values['en'],
                        'updated_at' => now(),
                    ]);
                $updated++;
            } else {
                DB::table('translations')->insert([
                    'language_id' => $englishId,
                    'group' => 'zalo',
                    'key' => $key,
                    'value' => $values['en'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $inserted++;
            }

            // Insert Vietnamese translation
            $existingVi = DB::table('translations')
                ->where('language_id', $vietnameseId)
                ->where('group', 'zalo')
                ->where('key', $key)
                ->first();

            if ($existingVi) {
                DB::table('translations')
                    ->where('id', $existingVi->id)
                    ->update([
                        'value' => $values['vi'],
                        'updated_at' => now(),
                    ]);
                $updated++;
            } else {
                DB::table('translations')->insert([
                    'language_id' => $vietnameseId,
                    'group' => 'zalo',
                    'key' => $key,
                    'value' => $values['vi'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $inserted++;
            }
        }

        $this->command->info("✅ Zalo translations seeded successfully!");
        $this->command->info("📊 Inserted: {$inserted} | Updated: {$updated}");
        $this->command->info("🔑 Total translation keys: " . count($translations));
    }
}
