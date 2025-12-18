<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoursePost;
use App\Models\CoursePostComment;
use App\Models\CoursePostLike;
use App\Services\CalendarEventService;
use App\Services\CloudflareService;
use App\Services\WebSocketService;
use App\Services\ZaloNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CoursePostController extends Controller
{
    protected $calendarEventService;
    protected $zalo;

    public function __construct(CalendarEventService $calendarEventService, ZaloNotificationService $zalo)
    {
        $this->calendarEventService = $calendarEventService;
        $this->zalo = $zalo;
    }

    // List posts for a class
    public function index(Request $request, $classId)
    {
        try {
            $posts = CoursePost::with(['user', 'media', 'comments.user'])
                ->where('class_id', $classId)
                ->when($request->post_type, fn($q, $type) => $q->where('post_type', $type))
                ->orderBy('is_pinned', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);

            // Add is_liked_by_current_user flag and submissions count for homework
            $currentUser = auth()->user();
            foreach ($posts as $post) {
                $post->is_liked = $post->isLikedBy($currentUser);
                
                // For homework posts, add submissions count (only count actual submissions)
                if ($post->post_type === 'homework' && (isset($post->metadata['homework_id']) || isset($post->metadata['homework_assignment_id']))) {
                    // Use homework_assignment_id if available, otherwise fall back to homework_id
                    $assignmentId = $post->metadata['homework_assignment_id'] ?? $post->metadata['homework_id'];

                    $submissionsCount = \App\Models\HomeworkSubmission::where('homework_assignment_id', $assignmentId)
                        ->whereIn('status', ['submitted', 'graded'])
                        ->count();

                    // Add to metadata
                    $metadata = $post->metadata ?? [];
                    $metadata['submissions_count'] = $submissionsCount;
                    
                    // Ensure homework_id is set for frontend compatibility
                    if (!isset($metadata['homework_id']) && isset($post->metadata['homework_assignment_id'])) {
                        $metadata['homework_id'] = $post->metadata['homework_assignment_id'];
                    }
                    
                    $post->metadata = $metadata;
                }
            }

            return response()->json($posts);
        } catch (\Exception $e) {
            Log::error('Error loading posts', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error loading posts'], 500);
        }
    }

    // Get upcoming events for sidebar
    public function getUpcomingEvents(Request $request)
    {
        try {
            $user = auth()->user();
            
            Log::info('[CoursePost] Loading upcoming events for user', [
                'user_id' => $user->id,
                'user_roles' => $user->roles->pluck('name')->toArray(),
            ]);
            
            // Get upcoming events from calendar_events table
            $query = \App\Models\CalendarEvent::query()
                ->where('category', 'event')
                ->where('start_date', '>=', now())
                ->orderBy('start_date', 'asc')
                ->limit(20);
            
            // Apply access control based on user role
            if (!$user->hasRole('super-admin') && !$user->hasRole('admin')) {
                $query->accessibleBy($user);
            }
            
            $events = $query->get()->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description, // Full content for display
                    'start_date' => $event->start_date,
                    'end_date' => $event->end_date,
                    'location' => $event->metadata['event_location'] ?? null,
                    'is_all_day' => $event->is_all_day ?? false,
                    'class_id' => $event->metadata['class_id'] ?? null,
                    'class_code' => $event->metadata['class_code'] ?? null,
                ];
            });

            Log::info('[CoursePost] Found upcoming events', ['count' => $events->count()]);

            return response()->json([
                'success' => true,
                'data' => $events
            ]);
        } catch (\Exception $e) {
            Log::error('[CoursePost] Error loading upcoming events', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Error loading upcoming events'], 500);
        }
    }

    // Get classes accessible by current user
    public function getMyClasses(Request $request)
    {
        try {
            $user = auth()->user();
            $branchId = $request->header('X-Branch-Id');

            Log::info('[CoursePost] 🔵 getMyClasses called', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'branch_id' => $branchId
            ]);

            $classes = collect();

            // Check if user is admin/super-admin first (highest priority)
            if ($user->hasRole('admin') || $user->hasRole('super-admin') || $user->hasPermission('course.manage')) {
                Log::info('[CoursePost] 👑 User is admin/super-admin or has course.manage');
                $query = \App\Models\ClassModel::select('id', 'name', 'code')
                    ->where('status', 'active'); // Only active classes

                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }

                $classes = $query->get();
                Log::info('[CoursePost] 📚 Found all active classes', ['count' => $classes->count()]);
            }
            // Check if user is a student
            elseif ($student = \App\Models\Student::where('user_id', $user->id)->first()) {
                Log::info('[CoursePost] 👨‍🎓 User is a student', ['student_id' => $student->id]);
                // Get classes where student is enrolled (via class_students pivot table)
                $query = $student->classes()
                    ->where('class_students.status', 'active')
                    ->where('classes.status', 'active') // Only active classes
                    ->select('classes.id', 'classes.name', 'classes.code');

                if ($branchId) {
                    $query->where('classes.branch_id', $branchId);
                }

                $classes = $query->get();
                Log::info('[CoursePost] 📚 Found active classes for student', ['count' => $classes->count()]);
            }
            // Check if user is a teacher
            elseif ($user->hasRole('teacher')) {
                Log::info('[CoursePost] 👨‍🏫 User is a teacher');
                // Get classes where user is homeroom teacher OR assigned as subject teacher
                $query = \App\Models\ClassModel::where(function($q) use ($user) {
                        $q->where('homeroom_teacher_id', $user->id)
                          ->orWhereHas('schedules', function($sq) use ($user) {
                              $sq->where('teacher_id', $user->id);
                          });
                    })
                    ->where('status', 'active') // Only active classes
                    ->select('id', 'name', 'code')
                    ->distinct();

                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }

                $classes = $query->get();
                Log::info('[CoursePost] 📚 Found active classes for teacher', ['count' => $classes->count()]);
            }
            // Check if user is a parent
            elseif ($parent = \App\Models\ParentModel::where('user_id', $user->id)->first()) {
                Log::info('[CoursePost] 👪 User is a parent', ['parent_id' => $parent->id]);
                // Get all students linked to this parent - use user_id since class_students.student_id stores user_id
                $studentUserIds = $parent->students()->pluck('students.user_id')->toArray();
                Log::info('[CoursePost] 👶 Parent\'s children (user_ids)', ['user_ids' => $studentUserIds]);

                // Get all classes where any of their children are enrolled
                $query = \App\Models\ClassModel::whereHas('students', function ($q) use ($studentUserIds) {
                        $q->whereIn('class_students.student_id', $studentUserIds)
                          ->where('class_students.status', 'active');
                    })
                    ->where('status', 'active') // Only active classes
                    ->select('id', 'name', 'code');

                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }

                $classes = $query->get();
                Log::info('[CoursePost] 📚 Found active classes for parent\'s children', ['count' => $classes->count()]);
            } else {
                Log::warning('[CoursePost] ⚠️ User does not match any role/permission criteria');
            }

            Log::info('[CoursePost] ✅ Returning classes', [
                'count' => $classes->count(),
                'classes' => $classes->toArray()
            ]);

            return response()->json([
                'success' => true,
                'data' => $classes
            ]);
        } catch (\Exception $e) {
            Log::error('[CoursePost] ❌ Error loading classes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error loading classes'], 500);
        }
    }

    // Create new post
    public function store(Request $request)
    {
        // Authorization check based on post type
        $postType = $request->input('post_type', 'text');
        
        if ($postType === 'event') {
            if (!$request->user()->hasPermission('course.create_event')) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.unauthorized_create_event')
                ], 403);
            }
        } elseif ($postType === 'homework') {
            if (!$request->user()->hasPermission('course.create_homework')) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.unauthorized_create_homework')
                ], 403);
            }
        } else {
            // Regular post
            if (!$request->user()->hasPermission('course.post')) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.unauthorized_post')
                ], 403);
            }
        }
        
        try {
            $validated = $request->validate([
                'class_id' => 'required|exists:classes,id',
                'content' => 'required|string',
                'post_type' => 'nullable|in:text,announcement,material,assignment,event,homework',
                'media' => 'nullable|array',
                'media.*' => 'file|mimes:jpg,jpeg,png,gif,mp4,mov,avi,webm|max:204800', // 200MB max
                // Event fields
                'event_start_date' => 'nullable|date',
                'event_end_date' => 'nullable|date|after_or_equal:event_start_date',
                'event_location' => 'nullable|string|max:255',
                'is_all_day' => 'nullable|boolean',
                'event_attendees' => 'nullable|array',
                'event_attendees.*' => 'exists:users,id',
                // Metadata for homework and other post types
                'metadata' => 'nullable|array',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed for course post', [
                'errors' => $e->errors(),
                'request_data' => $request->except(['media']),
                'has_media' => $request->hasFile('media'),
            ]);
            throw $e;
        }

        try {
            DB::beginTransaction();

            // Get class to determine branch_id
            $class = \App\Models\ClassModel::findOrFail($validated['class_id']);
            
            $post = CoursePost::create([
                'class_id' => $validated['class_id'],
                'user_id' => auth()->id(),
                'content' => $validated['content'],
                'post_type' => $validated['post_type'] ?? 'text',
                'branch_id' => $class->branch_id, // Use class's branch
                // Event fields
                'event_start_date' => $validated['event_start_date'] ?? null,
                'event_end_date' => $validated['event_end_date'] ?? $validated['event_start_date'] ?? null,
                'event_location' => $validated['event_location'] ?? null,
                'is_all_day' => $validated['is_all_day'] ?? false,
                'event_attendees' => $validated['event_attendees'] ?? null,
                // Metadata (for homework, etc.)
                'metadata' => $validated['metadata'] ?? null,
            ]);

            // Handle media uploads if any
            if ($request->hasFile('media')) {
                $sortOrder = 0;
                $useCloudflare = config('services.cloudflare.enabled', false);

                if ($useCloudflare) {
                    $cloudflareService = new CloudflareService();
                }

                foreach ($request->file('media') as $file) {
                    $mimeType = $file->getMimeType();
                    $isImage = str_starts_with($mimeType, 'image/');
                    $isVideo = str_starts_with($mimeType, 'video/');

                    // Try Cloudflare first if enabled, fallback to local storage
                    $uploaded = false;

                    if ($useCloudflare && $isImage) {
                        $metadata = [
                            'post_id' => $post->id,
                            'uploaded_by' => $request->user()->id,
                            'uploaded_at' => now()->toIso8601String(),
                        ];

                        $result = $cloudflareService->uploadImage($file, $metadata);

                        if ($result['success']) {
                            $post->media()->create([
                                'media_type' => 'image',
                                'file_name' => $result['filename'],
                                'file_path' => $result['id'],
                                'mime_type' => $mimeType,
                                'file_size' => $file->getSize(),
                                'url' => $result['variants'][0] ?? null,
                                'sort_order' => $sortOrder++,
                            ]);
                            $uploaded = true;
                        }
                    } elseif ($useCloudflare && $isVideo) {
                        $metadata = [
                            'post_id' => $post->id,
                            'uploaded_by' => $request->user()->id,
                            'uploaded_at' => now()->toIso8601String(),
                        ];

                        $result = $cloudflareService->uploadVideo($file, $metadata);

                        if ($result['success']) {
                            $post->media()->create([
                                'media_type' => 'video',
                                'file_name' => $file->getClientOriginalName(),
                                'file_path' => $result['uid'],
                                'mime_type' => $mimeType,
                                'file_size' => $file->getSize(),
                                'url' => $result['playback']['hls'] ?? null,
                                'sort_order' => $sortOrder++,
                            ]);
                            $uploaded = true;
                        }
                    }

                    // Fallback to local storage
                    if (!$uploaded) {
                        $path = $file->store('course-posts', 'public');
                        $mediaType = $isImage ? 'image' : ($isVideo ? 'video' : 'document');

                        $post->media()->create([
                            'media_type' => $mediaType,
                            'file_name' => $file->getClientOriginalName(),
                            'file_path' => $path,
                            'mime_type' => $mimeType,
                            'file_size' => $file->getSize(),
                            'url' => asset('storage/' . $path),
                            'sort_order' => $sortOrder++,
                        ]);
                    }
                }
            }

            // AUTO-CREATE HOMEWORK ASSIGNMENT when posting homework
            if ($post->post_type === 'homework' && isset($validated['metadata'])) {
                $metadata = $validated['metadata'];

                // Extract homework_id from metadata (if coming from homework_bank)
                $hwIds = [];
                if (isset($metadata['homework_id'])) {
                    $hwIds = [$metadata['homework_id']];
                }

                // Create HomeworkAssignment record
                $homework = \App\Models\HomeworkAssignment::create([
                    'class_id' => $validated['class_id'],
                    'title' => $metadata['homework_title'] ?? $metadata['custom_title'] ?? 'Homework Assignment',
                    'description' => $validated['content'],
                    'deadline' => $metadata['due_date'] ?? $metadata['custom_deadline'] ?? null,
                    'hw_ids' => $hwIds, // Array of homework_bank IDs
                    'assigned_to' => $metadata['assigned_to'] ?? null, // null = all students
                    'status' => 'active',
                    'assigned_by' => auth()->id(),
                    'branch_id' => $class->branch_id,
                ]);

                // Update post metadata to include homework_assignment_id for linking
                // Also add homework_id for frontend compatibility
                $post->update([
                    'metadata' => array_merge($metadata, [
                        'homework_assignment_id' => $homework->id,
                        'homework_id' => $homework->id, // For frontend compatibility
                    ])
                ]);

                Log::info('[CoursePost] Auto-created HomeworkAssignment from homework post', [
                    'post_id' => $post->id,
                    'homework_assignment_id' => $homework->id,
                    'class_id' => $validated['class_id'],
                ]);

                // AUTO-CREATE ZALO REMINDER if class has zalo_group_id and homework has deadline
                if ($class->zalo_group_id && $homework->deadline) {
                    try {
                        $zaloAccount = \App\Models\ZaloAccount::where('is_connected', true)->first();
                        
                        if ($zaloAccount) {
                            // Convert deadline to timestamp (milliseconds)
                            if ($homework->deadline instanceof \Carbon\Carbon) {
                                $deadline = $homework->deadline->timestamp * 1000;
                            } elseif (is_string($homework->deadline)) {
                                $deadline = strtotime($homework->deadline) * 1000;
                            } else {
                                $deadline = $homework->deadline * 1000;
                            }
                            
                            $reminderTitle = '📚 Bài tập: ' . $homework->title;
                            
                            Log::info('[CoursePost] Creating Zalo reminder for homework', [
                                'homework_id' => $homework->id,
                                'class_id' => $class->id,
                                'zalo_group_id' => $class->zalo_group_id,
                                'deadline' => $deadline,
                                'title' => $reminderTitle,
                            ]);
                            
                            $result = $this->zalo->createReminder(
                                $class->zalo_group_id,
                                $reminderTitle,
                                $deadline,
                                'group',
                                '📚',
                                0,
                                $zaloAccount->id
                            );
                            
                            if ($result['success'] ?? false) {
                                Log::info('[CoursePost] Zalo reminder created successfully', [
                                    'homework_id' => $homework->id,
                                    'reminder_result' => $result,
                                ]);
                            } else {
                                Log::warning('[CoursePost] Failed to create Zalo reminder', [
                                    'homework_id' => $homework->id,
                                    'error' => $result['message'] ?? 'Unknown error',
                                ]);
                            }
                        } else {
                            Log::info('[CoursePost] No connected Zalo account found, skipping reminder');
                        }
                    } catch (\Exception $e) {
                        Log::error('[CoursePost] Exception while creating Zalo reminder', [
                            'homework_id' => $homework->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            }

            // Sync with Calendar if this is an event
            if ($post->isEvent()) {
                $this->syncPostEventToCalendar($post);
            }

            DB::commit();

            // Emit WebSocket event for real-time updates
            WebSocketService::emitToRoom("classroom:{$post->class_id}", 'classroom:post:created', [
                'class_id' => $post->class_id,
                'post' => $post->load(['user', 'media', 'calendarEvent']),
            ]);

            return response()->json([
                'message' => 'Post created successfully',
                'data' => $post->load(['user', 'media', 'calendarEvent'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating post', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error creating post'], 500);
        }
    }

    // Get comments for a post
    public function getComments($classId, $postId)
    {
        try {
            // Get all comments (including replies) for this post
            $comments = CoursePostComment::with(['user', 'parent.user'])
                ->where('post_id', $postId)
                ->orderBy('created_at', 'asc')
                ->get();

            // Add is_liked flag and parent_user_name for current user
            $currentUser = auth()->user();
            foreach ($comments as $comment) {
                $comment->is_liked = CoursePostLike::where([
                    'likeable_type' => 'App\Models\CoursePostComment',
                    'likeable_id' => $comment->id,
                    'user_id' => $currentUser->id,
                ])->exists();
                
                // Add parent user name if replying
                $comment->parent_user_name = $comment->parent && $comment->parent->user 
                    ? $comment->parent->user->name 
                    : null;
            }

            return response()->json([
                'success' => true,
                'data' => $comments
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading comments', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error loading comments'], 500);
        }
    }

    // Add comment to post
    public function addComment(Request $request, $classId, $postId)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:course_post_comments,id',
        ]);

        try {
            $post = CoursePost::findOrFail($postId);

            $comment = CoursePostComment::create([
                'post_id' => $postId,
                'user_id' => auth()->id(),
                'parent_id' => $validated['parent_id'] ?? null,
                'content' => $validated['content'],
            ]);

            $post->incrementCommentsCount();

            // Emit WebSocket event for real-time updates
            WebSocketService::emitToRoom("classroom:{$post->class_id}", 'classroom:comment:created', [
                'class_id' => $post->class_id,
                'comment' => $comment->load('user'),
            ]);

            return response()->json([
                'message' => 'Comment added successfully',
                'data' => $comment->load('user')
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error adding comment', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error adding comment'], 500);
        }
    }

    // Toggle like on post or comment
    public function toggleLike(Request $request)
    {
        $validated = $request->validate([
            'likeable_type' => 'required|in:App\Models\CoursePost,App\Models\CoursePostComment',
            'likeable_id' => 'required|integer',
            'reaction_type' => 'nullable|in:like,love,support,celebrate,insightful,funny',
        ]);

        try {
            $existing = CoursePostLike::where([
                'likeable_type' => $validated['likeable_type'],
                'likeable_id' => $validated['likeable_id'],
                'user_id' => auth()->id(),
            ])->first();

            if ($existing) {
                // If same reaction, remove it (unlike)
                if ($existing->reaction_type === ($validated['reaction_type'] ?? 'like')) {
                    $existing->delete();
                    $likeable = $validated['likeable_type']::find($validated['likeable_id']);
                    $likeable->decrementLikesCount();
                    $action = 'removed';
                } else {
                    // Change reaction type
                    $existing->update(['reaction_type' => $validated['reaction_type'] ?? 'like']);
                    $action = 'added';
                }
            } else {
                CoursePostLike::create([
                    'likeable_type' => $validated['likeable_type'],
                    'likeable_id' => $validated['likeable_id'],
                    'user_id' => auth()->id(),
                    'reaction_type' => $validated['reaction_type'] ?? 'like',
                ]);
                $likeable = $validated['likeable_type']::find($validated['likeable_id']);
                $likeable->incrementLikesCount();
                $action = 'added';
            }

            // Emit WebSocket event for real-time reactions (only for posts)
            if ($validated['likeable_type'] === 'App\Models\CoursePost') {
                $post = CoursePost::find($validated['likeable_id']);
                if ($post) {
                    WebSocketService::emitToRoom("classroom:{$post->class_id}", 'classroom:post:reaction', [
                        'class_id' => $post->class_id,
                        'post_id' => $post->id,
                        'user_id' => auth()->id(),
                        'action' => $action,
                    ]);
                }
            }

            return response()->json([
                'message' => ucfirst($action) . ' successfully',
                'action' => $action,
                'likes_count' => $likeable->likes_count ?? 0,
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling like', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error processing like'], 500);
        }
    }

    // Delete comment
    public function deleteComment($classId, $postId, $commentId)
    {
        try {
            $comment = CoursePostComment::findOrFail($commentId);

            // Authorization check
            if ($comment->user_id !== auth()->id() && !auth()->user()->hasPermission('course.manage')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Get post to decrement comment count
            $post = CoursePost::findOrFail($postId);

            // Count replies that will be deleted
            $repliesCount = CoursePostComment::where('parent_id', $commentId)->count();

            // Delete all replies first
            CoursePostComment::where('parent_id', $commentId)->delete();

            // Then delete the parent comment
            $comment->delete();

            // Update post comment count
            $post->decrement('comments_count', 1 + $repliesCount);

            // Emit WebSocket event for real-time updates
            WebSocketService::emitToRoom("classroom:{$post->class_id}", 'classroom:comment:deleted', [
                'class_id' => $post->class_id,
                'post_id' => $postId,
                'comment_id' => $commentId,
            ]);

            return response()->json(['message' => 'Comment deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting comment', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error deleting comment'], 500);
        }
    }

    // Delete post
    public function destroy($classId, $id)
    {
        try {
            $post = CoursePost::where('class_id', $classId)->findOrFail($id);

            // Authorization check
            if ($post->user_id !== auth()->id() && !auth()->user()->hasPermission('course.manage')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Delete calendar event if exists
            if ($post->isEvent()) {
                $this->calendarEventService->deleteEvent($post);
            }

            // Delete associated HomeworkAssignment if this is a homework post
            if ($post->post_type === 'homework' && isset($post->metadata['homework_assignment_id'])) {
                $homeworkAssignmentId = $post->metadata['homework_assignment_id'];
                $homework = \App\Models\HomeworkAssignment::find($homeworkAssignmentId);
                if ($homework) {
                    $homework->delete();
                    Log::info('[CoursePost] Deleted associated HomeworkAssignment', [
                        'post_id' => $post->id,
                        'homework_assignment_id' => $homeworkAssignmentId,
                    ]);
                }
            }

            $post->delete();

            // Emit WebSocket event for real-time updates
            WebSocketService::emitToRoom("classroom:{$classId}", 'classroom:post:deleted', [
                'class_id' => $classId,
                'post_id' => $id,
            ]);

            return response()->json(['message' => 'Post deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting post', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Error deleting post: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sync course post event to calendar
     */
    protected function syncPostEventToCalendar(CoursePost $post)
    {
        if (!$post->isEvent()) {
            return;
        }

        $post->load('class', 'user');

        // Build attendees list (include post creator + tagged users)
        $attendees = $post->event_attendees ?? [];
        if (!in_array($post->user_id, $attendees)) {
            $attendees[] = $post->user_id;
        }

        $eventData = [
            'title' => "[{$post->class->code}] " . \Illuminate\Support\Str::limit($post->content, 50),
            'description' => $post->content,
            'category' => 'event', // Course event category
            'start_date' => $post->event_start_date,
            'end_date' => $post->event_end_date ?? $post->event_start_date,
            'is_all_day' => $post->is_all_day,
            'status' => 'pending',
            'user_id' => $post->user_id, // Creator
            'branch_id' => $post->branch_id,
            'created_by' => $post->user_id,
            'attendees' => $attendees,
            'location' => $post->event_location,
            'has_reminder' => true,
            'reminder_minutes_before' => 60, // Nhắc trước 1 tiếng
            'metadata' => [
                'class_id' => $post->class_id,
                'class_name' => $post->class->name,
                'class_code' => $post->class->code,
                'post_id' => $post->id,
                'post_type' => $post->post_type,
            ],
        ];

        $this->calendarEventService->syncEvent($post, $eventData);
    }
}
