# Translation Keys Cần Thêm Vào Database

## Module: Course/Homework

Cần thêm các translation keys sau vào bảng `translations` với `group = 'course'`:

### Frontend (ClassroomBoard.vue):

| Key | Vietnamese | English | Description |
|-----|-----------|---------|-------------|
| `view_submissions` | Xem danh sách nộp bài | View submissions | Nút xem danh sách |
| `hide_submissions` | Ẩn danh sách nộp bài | Hide submissions | Nút ẩn danh sách |
| `no_submissions` | Chưa có học viên nào nộp bài | No submissions yet | Thông báo không có ai nộp |
| `view_file` | Xem file | View file | Link xem file đã nộp |

### Backend (HomeworkAssignmentController.php):

| Key | Vietnamese | English | Description |
|-----|-----------|---------|-------------|
| `check_homework_details_below` | Kiểm tra chi tiết bài tập bên dưới | Check homework details below | Nội dung mặc định khi không có description |

### Common (Đã dùng nhưng cần kiểm tra):

| Key | Vietnamese | English | Description |
|-----|-----------|---------|-------------|
| `common.unknown` | Không rõ | Unknown | Hiển thị khi không có tên |

## Cách thêm vào database:

```sql
-- Thêm translation keys cho tiếng Việt (language_id = 1)
INSERT INTO translations (language_id, `group`, `key`, value, created_at, updated_at) VALUES
(1, 'course', 'view_submissions', 'Xem danh sách nộp bài', NOW(), NOW()),
(1, 'course', 'hide_submissions', 'Ẩn danh sách nộp bài', NOW(), NOW()),
(1, 'course', 'no_submissions', 'Chưa có học viên nào nộp bài', NOW(), NOW()),
(1, 'course', 'view_file', 'Xem file', NOW(), NOW()),
(1, 'course', 'check_homework_details_below', 'Kiểm tra chi tiết bài tập bên dưới', NOW(), NOW());

-- Thêm translation keys cho tiếng Anh (language_id = 2)
INSERT INTO translations (language_id, `group`, `key`, value, created_at, updated_at) VALUES
(2, 'course', 'view_submissions', 'View submissions', NOW(), NOW()),
(2, 'course', 'hide_submissions', 'Hide submissions', NOW(), NOW()),
(2, 'course', 'no_submissions', 'No submissions yet', NOW(), NOW()),
(2, 'course', 'view_file', 'View file', NOW(), NOW()),
(2, 'course', 'check_homework_details_below', 'Check homework details below', NOW(), NOW());
```

## Translation keys ĐANG được sử dụng (đã có trong code):

✅ Các keys này đã dùng translation đúng cách:
- `course.select_class`
- `course.post`
- `course.event`
- `course.homework`
- `course.create_homework`
- `course.homework_title`
- `course.select_session`
- `course.select_files`
- `course.no_files_available`
- `course.all_students`
- `course.specific_students`
- `course.deadline`
- `course.no_deadline`
- `course.all_day`
- `course.upcoming_events`
- `course.no_events`
- `course.upcoming_homework`
- `course.no_homework`
- `course.no_posts`
- `course.comments`
- `course.like`
- `course.comment`
- `course.author`
- `course.replying_to`
- `course.reply`
- `course.view_all_comments`
- `course.hide_comments`
- `course.write_comment`
- `common.posting`
- `common.creating`
- `common.unknown` (cần thêm nếu chưa có)

## Backend API Messages (Backend):

⚠️ **Lưu ý:** Backend API messages hiện đang hardcode English. Nếu cần đa ngôn ngữ cho API responses, cần:

1. Sử dụng `__('key')` thay vì hardcode string
2. Tạo translation group mới (ví dụ: `api_messages`) cho các error/success messages
3. Client-side sẽ xử lý hiển thị messages theo ngôn ngữ người dùng

## Summary:

- ✅ **Frontend:** Đã chuyển sang translation (trừ 5 keys mới cần thêm)
- ⚠️ **Backend content:** 1 key cần thêm
- ⚠️ **Backend API messages:** Vẫn hardcode English (có thể cải thiện sau)

**Total keys cần thêm:** 5 keys (course group)

