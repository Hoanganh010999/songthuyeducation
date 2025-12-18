<template>
  <div class="flex flex-col h-full overflow-hidden">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200">
            <img 
              v-if="item.avatar_url" 
              :src="item.avatar_url" 
              :alt="item.name"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center bg-blue-100">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900">{{ item.name || item.displayName }}</h3>
            <p v-if="itemType === 'groups'" class="text-sm text-gray-500">
              {{ item.members_count || 0 }} {{ t('zalo.members') }}
            </p>
            <p v-else class="text-sm text-gray-500">
              {{ item.phone || '' }}
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <!-- Refresh/Sync History button -->
          <button
            @click="handleRefreshMessages"
            :disabled="loadingMessages"
            class="p-2 rounded-lg hover:bg-gray-100 disabled:opacity-50"
            :title="t('zalo.refresh_messages')"
          >
            <svg
              class="w-5 h-5 text-gray-600"
              :class="{ 'animate-spin': loadingMessages }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
          <button class="p-2 rounded-lg hover:bg-gray-100">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </button>
          <button class="p-2 rounded-lg hover:bg-gray-100">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Messages area -->
    <div ref="messagesContainer" class="flex-1 overflow-y-auto bg-gray-50 px-6 py-4 space-y-4 min-h-0 relative">
      <!-- Loading overlay with spinner -->
      <div v-if="loadingMessages" class="absolute inset-0 bg-gray-50 bg-opacity-90 flex items-center justify-center z-10">
        <div class="text-center">
          <svg class="inline w-10 h-10 text-blue-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <p class="mt-2 text-sm text-gray-600">{{ t('common.loading') }}...</p>
        </div>
      </div>
      <div v-else-if="messages.length === 0" class="text-center py-8 text-gray-500">
        {{ t('zalo.no_messages') }}
      </div>
      <template v-else>
        <!-- Loading indicator for older messages -->
        <div v-if="loadingOlderMessages" class="text-center py-3 mb-2">
          <div class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-lg shadow-sm border border-gray-200">
            <svg class="w-4 h-4 text-blue-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span class="text-sm text-gray-600">Đang tải tin nhắn cũ hơn...</span>
          </div>
        </div>

        <div
          v-for="message in messages"
          :key="message.id"
          :id="`message-${message.id}`"
          class="flex gap-2 group"
          :class="message.type === 'sent' ? 'justify-end' : 'justify-start'"
        >
          <!-- Avatar for received group messages -->
          <div
            v-if="message.type === 'received' && message.recipient_type === 'group'"
            class="flex-shrink-0"
          >
            <img
              v-if="message.sender_avatar || message.avatar_url"
              :src="message.sender_avatar || message.avatar_url"
              :alt="message.sender_name"
              class="w-8 h-8 rounded-full"
            />
            <div
              v-else
              class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-xs font-semibold text-white"
            >
              {{ (message.sender_name || 'U').charAt(0).toUpperCase() }}
            </div>
          </div>
          
          <div
            class="max-w-md relative flex flex-col"
            :class="message.type === 'sent' ? 'items-end' : 'items-start'"
          >
            <!-- Sender name for group messages (received only) -->
            <div 
              v-if="message.type === 'received' && message.recipient_type === 'group' && message.sender_name"
              class="text-xs font-semibold text-gray-600 mb-1 px-1"
            >
              {{ message.sender_name }}
            </div>
            
            <!-- Message bubble (ONLY for non-file messages or when has quote) -->
            <div
              v-if="message.content_type !== 'file' || message.reply_to || message.quote_data"
              class="px-4 py-2 rounded-lg relative"
              :class="message.type === 'sent' 
                ? 'bg-blue-600 text-white' 
                : 'bg-white text-gray-900 border border-gray-200'"
            >
              <!-- Quoted message (if reply) -->
              <div 
                v-if="message.reply_to || message.quote_data"
                @click="scrollToQuotedMessage(message.reply_to?.id || message.reply_to?.message_id)"
                class="mb-2 pb-2 border-l-2 pl-2 cursor-pointer hover:bg-gray-50 transition-colors rounded"
                :class="message.type === 'sent' 
                  ? 'border-blue-300' 
                  : 'border-gray-300'"
              >
                <p class="text-xs font-semibold opacity-75 mb-1">
                  {{ message.reply_to?.sender_name || 'Unknown' }}
                </p>
                <p class="text-xs opacity-75 line-clamp-2">
                  {{ formatMessageContent(message.reply_to?.content || message.quote_data?.msg || message.quote_data?.content || '', message.reply_to?.content_type || message.quote_data?.cliMsgType) }}
                </p>
              </div>
              
              <!-- Message content - hide CDN URLs and file names -->
              <!-- 🔧 FIX: Also hide content for stickers (only show in sticker block below) -->
              <div
                v-if="message.content_type !== 'file' && message.content_type !== 'sticker'"
                class="text-sm whitespace-pre-wrap"
                v-html="formatMessageContent(message.content, message.content_type, true)"
              ></div>
              
              <!-- ULTIMATE FIX: Completely isolated image container with ALL CSS reset -->
              <div 
                v-if="message.content_type === 'image' && (message.media_url || message.content)"
                @click="openLightbox(message.media_url || message.content)"
                class="zalo-image-container"
              >
                <img 
                  :src="message.media_url || message.content" 
                  alt="Image"
                  class="zalo-image-preview"
                  @error="handleImageError"
                  @load="handleImageLoad"
                />
              </div>
            </div>
            
            <!-- File attachment display (like Zalo app) -->
            <div
              v-if="message.content_type === 'file' && (message.media_url || message.metadata?.file?.href)"
              class="mt-2"
            >
              <a
                :href="message.media_url || message.metadata?.file?.href"
                :download="message.metadata?.file?.title || message.content || 'file'"
                target="_blank"
                rel="noreferrer noopener"
                class="flex items-center gap-3 p-3 rounded-lg border transition-all hover:bg-gray-50"
                :class="message.type === 'sent' ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200'"
              >
                <!-- File icon -->
                <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded bg-blue-500 text-white">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                  </svg>
                </div>

                <!-- File info -->
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium truncate" :class="message.type === 'sent' ? 'text-blue-900' : 'text-gray-900'">
                    {{ message.metadata?.file?.title || message.content || 'Document' }}
                  </p>
                  <p class="text-xs" :class="message.type === 'sent' ? 'text-blue-600' : 'text-gray-500'">
                    {{ t('zalo.click_to_download') }}
                  </p>
                </div>

                <!-- Download icon -->
                <div class="flex-shrink-0">
                  <svg class="w-5 h-5" :class="message.type === 'sent' ? 'text-blue-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                  </svg>
                </div>
              </a>
            </div>

            <!-- Sticker display -->
            <div
              v-if="message.content_type === 'sticker'"
              class="inline-block mt-2"
            >
              <div v-if="message.metadata?.sticker?.stickerUrl || message.metadata?.sticker?.stickerWebpUrl || message.media_url" class="sticker-container">
                <img
                  :src="message.metadata?.sticker?.stickerUrl || message.metadata?.sticker?.stickerWebpUrl || message.media_url"
                  :alt="message.metadata?.sticker?.text || message.content || 'Sticker'"
                  class="max-w-[150px] max-h-[150px] object-contain cursor-pointer hover:scale-110 transition-transform"
                  @error="handleImageError"
                />
              </div>
              <div v-else class="flex items-center gap-2 px-3 py-2 bg-gray-100 rounded-lg text-gray-600 text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ message.content || 'Sticker' }}</span>
                <span v-if="message.metadata?.sticker" class="text-xs text-gray-400">
                  (ID: {{ message.metadata.sticker.id }})
                </span>
              </div>
            </div>

            <!-- Link display (if not a file) -->
            <div
              v-if="message.content_type === 'link' && message.media_url"
              class="px-4 py-2 rounded-lg relative mt-2"
              :class="message.type === 'sent'
                ? 'bg-blue-600 text-white'
                : 'bg-white text-gray-900 border border-gray-200'"
            >
              <a 
                :href="message.media_url" 
                target="_blank"
                :class="message.type === 'sent' ? 'text-blue-200 underline' : 'text-blue-600 underline'"
              >
                {{ t('zalo.open_link') }}
              </a>
            </div>
            
            <!-- Reactions (OUTSIDE bubble - for ALL message types) -->
            <div 
              v-if="message.reactions && message.reactions.length > 0" 
              class="flex flex-wrap gap-1 mt-1"
              :class="message.type === 'sent' ? 'justify-end' : 'justify-start'"
            >
              <button
                v-for="reaction in message.reactions"
                :key="reaction.reaction"
                @click="showReactionUsers(message.id, reaction)"
                class="px-2 py-0.5 rounded-full text-xs flex items-center gap-1 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm"
                :title="`${reaction.count} người đã ${reaction.reaction}`"
              >
                <span>{{ getReactionEmoji(reaction.reaction) }}</span>
                <span class="text-gray-700 font-medium">{{ reaction.count }}</span>
              </button>
            </div>
            
            <!-- Timestamp and actions (BELOW reactions) -->
            <div 
              class="flex items-center justify-between gap-2 mt-1"
              :class="message.type === 'sent' ? 'flex-row-reverse' : 'flex-row'"
            >
              <p class="text-xs text-gray-500">
                {{ formatTime(message.sent_at || message.created_at) }}
              </p>
              <!-- Action buttons (show on hover or when picker is open) -->
              <div class="flex items-center gap-1 transition-opacity" :class="showReactionPickerFor === message.id ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'">
                  <button
                    @click="startReply(message)"
                    class="p-1 rounded hover:bg-opacity-20"
                    :class="message.type === 'sent' ? 'hover:bg-white' : 'hover:bg-gray-200'"
                    :title="t('zalo.reply')"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                  </button>
                  <div class="relative" style="z-index: 10;">
                    <button
                      @click.stop="toggleReactionPicker(message.id)"
                      class="p-1 rounded hover:bg-opacity-20"
                      :class="message.type === 'sent' ? 'hover:bg-white' : 'hover:bg-gray-200'"
                      :title="t('zalo.react')"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </button>
                    <!-- Reaction picker dropdown -->
                    <div
                      v-if="showReactionPickerFor === message.id"
                      class="reaction-picker-container absolute bottom-full mb-2 bg-white rounded-lg shadow-xl border border-gray-300 p-2"
                      :class="message.type === 'sent' ? 'right-0' : 'left-0'"
                      style="min-width: 200px; z-index: 40; pointer-events: auto; max-width: calc(100vw - 400px);"
                      @click.stop
                    >
                      <div class="grid grid-cols-6 gap-1">
                        <button
                          v-for="reaction in availableReactions"
                          :key="reaction.icon"
                          @click.stop="addReaction(message, reaction.icon)"
                          class="p-2 hover:bg-gray-100 rounded text-lg transition-colors cursor-pointer"
                          :title="reaction.name"
                        >
                          {{ reaction.emoji }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
            </div>
          </div>
        </div>
      </template>
      
      <!-- Reply input (shown when replying) -->
      <div
        v-if="replyingTo"
        class="px-6 py-3 border-t border-gray-200 bg-gray-50 flex-shrink-0"
      >
        <div class="flex items-start gap-2">
          <div class="flex-1">
            <div class="mb-2 px-3 py-2 bg-white rounded-lg border-l-2 border-blue-500">
              <p class="text-xs font-semibold text-gray-600 mb-1">
                {{ t('zalo.replying_to') || 'Replying to' }}: {{ replyingTo.sender_name || 'Unknown' }}
              </p>
              <p class="text-xs text-gray-500 line-clamp-2">
                {{ replyingTo.content }}
              </p>
            </div>
            <textarea
              v-model="replyText"
              @keydown.enter.exact.prevent="sendReply"
              @keydown.enter.shift.exact="replyText += '\n'"
              :placeholder="t('zalo.type_reply') || 'Type your reply...'"
              rows="1"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
              style="min-height: 40px; max-height: 120px;"
            ></textarea>
          </div>
          <div class="flex items-end gap-2">
            <button
              @click="cancelReply"
              class="p-2 text-gray-600 hover:text-gray-900"
              :title="t('common.cancel') || 'Cancel'"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
            <button
              @click="sendReply"
              :disabled="!replyText.trim() || sendingReply"
              class="p-2 rounded-lg transition-colors"
              :class="replyText.trim() && !sendingReply
                ? 'bg-blue-600 text-white hover:bg-blue-700' 
                : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Input area -->
    <div class="px-6 py-4 border-t border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-end gap-2">
        <!-- File upload button -->
        <label class="p-2 text-gray-600 hover:text-gray-900 cursor-pointer">
          <input type="file" ref="fileInput" @change="handleFileSelect" class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip,.rar">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
          </svg>
        </label>
        <!-- Image upload button -->
        <label class="p-2 text-gray-600 hover:text-gray-900 cursor-pointer">
          <input type="file" ref="imageInput" @change="handleImageSelect" class="hidden" accept="image/*">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </label>
        <!-- Sticker button -->
        <button
          @click="showStickerPicker = !showStickerPicker"
          class="p-2 text-gray-600 hover:text-gray-900 cursor-pointer relative"
          :class="{ 'text-blue-600': showStickerPicker }"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </button>
        <div class="flex-1">
          <textarea
            v-model="messageText"
            @keydown.enter.exact.prevent="sendMessage"
            @keydown.enter.shift.exact="messageText += '\n'"
            :placeholder="t('zalo.type_message')"
            rows="1"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
            style="min-height: 40px; max-height: 120px;"
          ></textarea>
        </div>
        <button
          @click="sendMessage"
          :disabled="(!messageText.trim() && !selectedFile && !selectedImage) || sending || uploading"
          class="p-2 rounded-lg transition-colors"
          :class="(messageText.trim() || selectedFile || selectedImage) && !sending && !uploading
            ? 'bg-blue-600 text-white hover:bg-blue-700' 
            : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
          </svg>
        </button>
      </div>
      <!-- Preview uploaded file/image -->
      <div v-if="uploading || selectedFile || selectedImage" class="mt-2 flex items-center gap-2">
        <div v-if="selectedFile" class="flex items-center gap-2 px-3 py-2 bg-gray-100 rounded-lg">
          <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
          </svg>
          <span class="text-sm text-gray-700">{{ selectedFile.name }}</span>
          <button @click="clearFile" class="text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div v-if="selectedImage" class="flex items-center gap-2 px-3 py-2 bg-gray-100 rounded-lg">
          <img :src="selectedImagePreview" class="w-10 h-10 object-cover rounded" alt="Preview">
          <span class="text-sm text-gray-700">{{ selectedImage.name }}</span>
          <button @click="clearImage" class="text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div v-if="uploading" class="text-sm text-gray-500">
          {{ t('common.uploading') }}...
        </div>
      </div>

      <!-- Sticker Picker -->
      <div class="relative">
        <ZaloStickerPicker
          :show="showStickerPicker"
          :account-id="accountId"
          @close="showStickerPicker = false"
          @select="handleStickerSelect"
        />
      </div>
    </div>

    <!-- Lightbox for full-size image -->
    <Teleport to="body">
      <Transition name="fade">
        <div 
          v-if="showLightbox"
          @click="closeLightbox"
          class="fixed inset-0 z-40 bg-black bg-opacity-90 flex items-center justify-center p-4"
        >
          <div class="relative max-w-7xl max-h-screen">
            <!-- Close button -->
            <button
              @click.stop="closeLightbox"
              class="absolute top-4 right-4 text-white hover:text-gray-300 bg-black bg-opacity-50 rounded-full p-2 z-10"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
            
            <!-- Full-size image -->
            <img 
              :src="lightboxImage"
              alt="Full size"
              class="max-w-full max-h-screen object-contain rounded-lg"
              @click.stop
            />
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick, inject } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import { useSwal } from '../../../composables/useSwal';
import { useZaloSocket } from '../../../composables/useZaloSocket';
import axios from 'axios';
import Swal from 'sweetalert2';
import { isRichTextFormat, getHtmlContent, getPlainText } from '../../../utils/zaloRichTextParser';
import ZaloStickerPicker from './ZaloStickerPicker.vue';

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  accountId: {
    type: Number,
    default: null,
  },
  itemType: {
    type: String,
    required: true, // 'friends' or 'groups'
  },
});

const emit = defineEmits(['message-sent']);

const { t } = useI18n();
const zaloAccount = inject('zaloAccount', null);
const zaloSocket = useZaloSocket();

// 🔥 FIX: Get account ID from props or fallback to zaloAccount
// This ensures we use the currently selected account
const currentAccountId = computed(() => {
  return props.accountId || zaloAccount?.activeAccountId?.value || null;
});

const messages = ref([]);
const messageText = ref('');
const loadingMessages = ref(false);
const loadingOlderMessages = ref(false); // For lazy loading
const hasMoreMessages = ref(false); // Whether there are more messages to load
const oldestMessageId = ref(null); // ID of oldest message (for pagination)
const sending = ref(false);
const uploading = ref(false);
const messagesContainer = ref(null);

// Race condition prevention
let currentLoadController = null; // AbortController for current request
const messagesCache = new Map(); // Cache messages by conversation ID
let currentConversationId = null; // Track current conversation
let currentLoadTimestamp = 0; // Timestamp to verify latest request
const fileInput = ref(null);
const imageInput = ref(null);
const selectedFile = ref(null);
const selectedImage = ref(null);
const selectedImagePreview = ref(null);

// Reply state
const replyingTo = ref(null);
const replyText = ref('');
const sendingReply = ref(false);

// Reaction state
const showReactionPickerFor = ref(null);
const lightboxImage = ref(null);
const showLightbox = ref(false);

// Sticker picker state
const showStickerPicker = ref(false);

// Zalo reaction icons (matching actual Zalo API format)
const availableReactions = [
  { icon: '/-heart', emoji: '❤️', name: 'Trái tim' },
  { icon: '/-strong', emoji: '👍', name: 'Thích' },
  { icon: ':>', emoji: '😂', name: 'Cười to' },
  { icon: ':o', emoji: '😮', name: 'Ngạc nhiên' },
  { icon: ':-((', emoji: '😢', name: 'Khóc to' },
  { icon: ':-h', emoji: '😠', name: 'Tức giận' },
];

// Determine recipient type from itemType prop
const recipientType = computed(() => {
  if (props.itemType === 'groups' || props.itemType === 'group') {
    return 'group';
  }
  return 'user';
});

// Lightbox functions
const openLightbox = (imageUrl) => {
  lightboxImage.value = imageUrl;
  showLightbox.value = true;
};

const closeLightbox = () => {
  showLightbox.value = false;
  lightboxImage.value = null;
};

// Debug image loading
const handleImageError = (event) => {
  console.error('❌ [Image Load Error]:', {
    src: event.target.src,
    error: 'Failed to load image',
    naturalWidth: event.target.naturalWidth,
    naturalHeight: event.target.naturalHeight,
  });
};

const handleImageLoad = (event) => {
  console.log('✅ [Image Loaded]:', {
    src: event.target.src.substring(0, 80) + '...',
    width: event.target.naturalWidth,
    height: event.target.naturalHeight,
    complete: event.target.complete,
  });
};

// Format message content - hide CDN URLs for images
const formatMessageContent = (content, contentType, asHtml = false) => {
  if (!content) return '';

  // If it's an image and content is a CDN URL, show generic message
  if (contentType === 'image' && (content.includes('zdn.vn') || content.includes('http') || content.includes('.jpg') || content.includes('.png'))) {
    return '📷 Hình ảnh'; // Use hardcoded text to ensure it always works
  }

  // Check if content is rich text format
  if (isRichTextFormat(content)) {
    return asHtml ? getHtmlContent(content) : getPlainText(content);
  }

  return content;
};

// Load messages with race condition prevention and caching
const loadMessages = async (loadAll = false, forceReload = false) => {
  if (!props.item?.id) return;

  const conversationId = props.item.id;
  const accountId = currentAccountId.value;

  if (!accountId) {
    console.warn('No active account');
    return;
  }

  // Cancel previous request if still pending
  if (currentLoadController) {
    console.log('🚫 [ZaloChatView] Aborting previous load request');
    currentLoadController.abort();
  }

  // Update current conversation ID and timestamp
  currentConversationId = conversationId;
  const requestTimestamp = Date.now();
  currentLoadTimestamp = requestTimestamp;

  console.log('🔄 [ZaloChatView] Loading messages:', {
    conversationId,
    requestTimestamp,
    hasCache: messagesCache.has(conversationId),
    forceReload,
  });

  // Check cache first (unless force reload)
  if (!forceReload && !loadAll && messagesCache.has(conversationId)) {
    const cachedMessages = messagesCache.get(conversationId);

    // 🔥 VALIDATION: Ensure cached messages are valid array
    if (Array.isArray(cachedMessages) && cachedMessages.length >= 0) {
      console.log('💾 [ZaloChatView] Loading from cache:', conversationId, 'messages:', cachedMessages.length);
      messages.value = cachedMessages;
      loadingMessages.value = false; // 🔥 FIX: Clear loading state when loading from cache
      await nextTick();
      setTimeout(() => scrollToBottom(), 100);
      return;
    } else {
      console.warn('⚠️ [ZaloChatView] Invalid cache data, removing:', conversationId, cachedMessages);
      messagesCache.delete(conversationId);
      // Continue to fetch from API
    }
  }

  loadingMessages.value = true;

  // Create new AbortController for this request
  currentLoadController = new AbortController();
  const requestConversationId = conversationId; // Capture for closure

  try {
    const params = {
      account_id: accountId,
      recipient_id: conversationId,
      recipient_type: recipientType.value,
    };

    // When refreshing, load ALL messages
    if (loadAll) {
      params.per_page = 99999;
    }

    console.log('📡 [ZaloChatView] Fetching messages for:', conversationId);

    const response = await axios.get('/api/zalo/messages', {
      params,
      signal: currentLoadController.signal
    });

    // Check if this conversation is still active AND this is the latest request
    if (requestConversationId !== currentConversationId || requestTimestamp !== currentLoadTimestamp) {
      console.log('⚠️ [ZaloChatView] Ignoring stale response:', {
        requestConvId: requestConversationId,
        currentConvId: currentConversationId,
        requestTime: requestTimestamp,
        currentTime: currentLoadTimestamp,
        isStale: requestTimestamp !== currentLoadTimestamp,
      });
      return;
    }

    if (response.data.success) {
      const loadedMessages = response.data.data || [];
      const meta = response.data.meta || {};

      // 🔥 VALIDATION: Ensure we're caching valid data
      if (!Array.isArray(loadedMessages)) {
        console.error('❌ [ZaloChatView] Invalid response data (not array):', loadedMessages);
        return;
      }

      // Update messages and cache
      messages.value = loadedMessages;
      messagesCache.set(conversationId, loadedMessages);

      // Update pagination metadata
      hasMoreMessages.value = meta.has_more || false;
      oldestMessageId.value = meta.oldest_id || null;

      console.log('✅ [ZaloChatView] Messages loaded and cached:', {
        conversationId,
        hasMore: hasMoreMessages.value,
        oldestId: oldestMessageId.value,
        messageCount: loadedMessages.length,
        isCurrentConv: conversationId === currentConversationId,
      });

      // Scroll to bottom after DOM update
      await nextTick();
      setTimeout(() => scrollToBottom(), 100);
    }
  } catch (error) {
    if (error.name === 'CanceledError' || error.code === 'ERR_CANCELED') {
      console.log('🚫 [ZaloChatView] Request aborted for:', requestConversationId);
    } else {
      console.error('Failed to load messages:', error);
    }
  } finally {
    // Only clear loading if this is still the current conversation AND latest request
    if (requestConversationId === currentConversationId && requestTimestamp === currentLoadTimestamp) {
      loadingMessages.value = false;
      currentLoadController = null;
      console.log('✅ [ZaloChatView] Load complete for:', conversationId);
    } else {
      console.log('⏭️ [ZaloChatView] Skipping cleanup for stale request:', requestConversationId);
    }
  }
};

// Clear all cache (helper function)
const clearAllCache = () => {
  console.log('🧹 [ZaloChatView] Clearing all message cache');
  messagesCache.clear();
  messages.value = [];
};

// Load older messages (lazy loading / infinite scroll)
const loadOlderMessages = async () => {
  if (!hasMoreMessages.value || loadingOlderMessages.value || !oldestMessageId.value) {
    console.log('⏭️ [ZaloChatView] Skipping load older messages:', {
      hasMore: hasMoreMessages.value,
      loading: loadingOlderMessages.value,
      oldestId: oldestMessageId.value,
    });
    return;
  }

  const conversationId = props.item?.id;
  const accountId = currentAccountId.value;

  if (!conversationId || !accountId) return;

  loadingOlderMessages.value = true;

  try {
    console.log('📜 [ZaloChatView] Loading older messages before ID:', oldestMessageId.value);

    // Save current scroll position
    const container = messagesContainer.value;
    const scrollHeightBefore = container?.scrollHeight || 0;

    const response = await axios.get('/api/zalo/messages', {
      params: {
        account_id: accountId,
        recipient_id: conversationId,
        recipient_type: recipientType.value,
        before_id: oldestMessageId.value, // Load messages before this ID
        per_page: 50,
      },
    });

    if (response.data.success) {
      const olderMessages = response.data.data || [];
      const meta = response.data.meta || {};

      console.log('✅ [ZaloChatView] Older messages loaded:', {
        count: olderMessages.length,
        hasMore: meta.has_more,
        oldestId: meta.oldest_id,
      });

      // Prepend older messages to the beginning
      messages.value = [...olderMessages, ...messages.value];

      // Update pagination metadata
      hasMoreMessages.value = meta.has_more || false;
      oldestMessageId.value = meta.oldest_id || null;

      // Update cache
      messagesCache.set(conversationId, messages.value);

      // Maintain scroll position
      await nextTick();
      if (container) {
        const scrollHeightAfter = container.scrollHeight;
        const scrollDiff = scrollHeightAfter - scrollHeightBefore;
        container.scrollTop += scrollDiff;
      }
    }
  } catch (error) {
    if (error.name !== 'AbortError') {
      console.error('❌ [ZaloChatView] Error loading older messages:', error);
    }
  } finally {
    loadingOlderMessages.value = false;
  }
};

// Handle scroll event for lazy loading
const handleScroll = () => {
  const container = messagesContainer.value;
  if (!container) return;

  // Check if scrolled to top (with 50px threshold)
  if (container.scrollTop < 50 && hasMoreMessages.value && !loadingOlderMessages.value) {
    console.log('🔝 [ZaloChatView] Scrolled to top, loading older messages...');
    loadOlderMessages();
  }
};

// Handle refresh messages button click
const handleRefreshMessages = async () => {
  if (loadingMessages.value) return;

  console.log('🔄 [ZaloChatView] Refreshing ALL messages for conversation:', props.item?.id);

  // Clear cache for this conversation
  if (props.item?.id) {
    messagesCache.delete(props.item.id);
  }

  // Clear current messages to force fresh load
  messages.value = [];

  // Reload ALL messages from database (with loadAll=true, forceReload=true)
  await loadMessages(true, true);

  // Show success notification
  useSwal().fire({
    icon: 'success',
    title: t('zalo.messages_refreshed') || 'Đã đồng bộ',
    text: t('zalo.messages_refreshed_desc') || 'Tin nhắn đã được làm mới với thời gian chính xác từ cơ sở dữ liệu',
    timer: 2000,
    showConfirmButton: false,
    toast: true,
    position: 'top-end',
  });
};

// Handle file select
const handleFileSelect = async (event) => {
  const file = event.target.files[0];
  if (!file) return;
  
  selectedFile.value = file;
  selectedImage.value = null;
  selectedImagePreview.value = null;
  
  // Don't auto-upload - wait for user to click Send button
  // This allows user to type a message along with the file
};

// Handle image select
const handleImageSelect = async (event) => {
  console.log('🖼️ [ZaloChatView] handleImageSelect called - image selected (NOT uploading yet)');
  
  const file = event.target.files[0];
  if (!file) {
    console.log('⚠️ [ZaloChatView] No file selected');
    return;
  }
  
  console.log('🖼️ [ZaloChatView] File selected:', {
    name: file.name,
    size: file.size,
    type: file.type,
    note: 'This is just selection, NOT upload. Upload will happen when Send button is clicked.',
  });
  
  // Create preview
  const reader = new FileReader();
  reader.onload = (e) => {
    selectedImagePreview.value = e.target.result;
    console.log('✅ [ZaloChatView] Image preview created');
  };
  reader.readAsDataURL(file);
  
  selectedImage.value = file;
  selectedFile.value = null;
  
  console.log('✅ [ZaloChatView] Image stored in selectedImage. Waiting for Send button click...');
  
  // Don't auto-upload - wait for user to click Send button
  // This allows user to type a message along with the image
};

// Upload file
const uploadFile = async (file, textMessage = '') => {
  if (!props.item?.id) return;
  
  uploading.value = true;
  try {
    const accountId = currentAccountId.value;
    if (!accountId) {
      throw new Error('No active account');
    }

    const formData = new FormData();
    formData.append('file', file);
    formData.append('account_id', accountId);
    formData.append('recipient_id', props.item.id);
    formData.append('recipient_type', props.itemType === 'groups' ? 'group' : 'user');

    // Upload file to server first (you'll need to create this endpoint)
    const uploadResponse = await axios.post('/api/zalo/messages/upload-file', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    if (uploadResponse.data.success) {
      // Send message with file URL, absolute path, and optional text
      const response = await axios.post('/api/zalo/messages/send', {
        account_id: accountId,
        recipient_id: props.item.id,
        recipient_type: props.itemType === 'groups' ? 'group' : 'user',
        message: textMessage || file.name, // Use text message if provided, otherwise use file name
        media_url: uploadResponse.data.data.url, // Public URL for frontend display
        media_path: uploadResponse.data.data.absolute_path, // Absolute path for zalo-service (no download needed!)
        content_type: 'file',
      });

      if (response.data.success) {
        // IMPORTANT: Do NOT push message here (optimistic update)
        // WebSocket will receive and add the message automatically
        
        console.log('✅ [ZaloChatView] File sent, waiting for WebSocket');
        
        emit('message-sent');
        clearFile();
        
        // Show success toast
        Swal.fire({
          icon: 'success',
          title: t('common.success'),
          text: t('zalo.file_sent') || 'File sent successfully',
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 2000,
        });
      } else {
        console.error('❌ [ZaloChatView] File send failed:', response.data);
        // Clear file on send failure
        clearFile();
      }
    } else {
      console.error('❌ [ZaloChatView] File upload response not successful:', uploadResponse.data);
      // Clear file on upload failure
      clearFile();
    }
  } catch (error) {
    console.error('❌ [ZaloChatView] Failed to upload file:', error);
    // Clear file on error
    clearFile();
  } finally {
    uploading.value = false;
  }
};

// Upload image
const uploadImage = async (file, textMessage = '') => {
  console.log('🚀 [ZaloChatView] uploadImage called - THIS IS WHERE UPLOAD STARTS');
  
  if (!props.item?.id) {
    console.error('❌ [ZaloChatView] uploadImage blocked: No item.id');
    return;
  }
  
  console.log('🚀 [ZaloChatView] uploadImage proceeding with:', {
    file_name: file?.name,
    file_size: file?.size,
    item_id: props.item?.id,
    textMessage_length: textMessage?.length || 0,
  });
  
  uploading.value = true;
  try {
    const accountId = currentAccountId.value;
    if (!accountId) {
      console.error('❌ [ZaloChatView] No active account for image upload');
      throw new Error('No active account');
    }

    // Ensure account_id is a number (Laravel expects integer)
    const accountIdNum = typeof accountId === 'string' ? parseInt(accountId, 10) : accountId;
    if (isNaN(accountIdNum)) {
      console.error('❌ [ZaloChatView] Invalid account_id:', accountId);
      throw new Error('Invalid account ID');
    }

    const formData = new FormData();
    
    // CRITICAL: Verify file before appending
    console.log('🔍 [ZaloChatView] File before FormData:', {
      has_file: !!file,
      is_file: file instanceof File,
      is_blob: file instanceof Blob,
      file_name: file?.name,
      file_size: file?.size,
      file_type: file?.type,
    });
    
    formData.append('image', file, file.name); // Include filename explicitly
    formData.append('account_id', accountIdNum.toString());
    formData.append('recipient_id', props.item.id.toString());
    formData.append('recipient_type', props.itemType === 'groups' ? 'group' : 'user');

    console.log('📤 [ZaloChatView] Uploading image:', {
      account_id: accountId,
      account_id_type: typeof accountId,
      account_id_num: accountIdNum,
      recipient_id: props.item.id,
      recipient_id_type: typeof props.item.id,
      has_text: !!textMessage,
      text_length: textMessage.length,
      file_name: file.name,
      file_size: file.size,
      file_type: file.type,
      has_file: !!file,
      file_is_file: file instanceof File,
    });
    
    // Log FormData contents (for debugging)
    console.log('📤 [ZaloChatView] FormData entries:', {
      has_image: formData.has('image'),
      account_id: formData.get('account_id'),
      recipient_id: formData.get('recipient_id'),
      recipient_type: formData.get('recipient_type'),
      formData_keys: Array.from(formData.keys()),
      image_entry: formData.get('image'), // Should be File object
    });

    // Upload image to server first
    // CRITICAL: Don't set Content-Type header - axios will set it automatically with boundary
    console.log('📡 [ZaloChatView] Sending FormData to server...');
    
    const uploadResponse = await axios.post('/api/zalo/messages/upload-image', formData, {
      headers: {
        // Don't set Content-Type - let axios handle it automatically
        'Accept': 'application/json',
      },
      // Ensure proper FormData handling
      maxContentLength: Infinity,
      maxBodyLength: Infinity,
      // Add timeout to prevent hanging
      timeout: 120000, // 2 minutes
    });
    
    console.log('📡 [ZaloChatView] Upload response status:', uploadResponse.status);

    console.log('📥 [ZaloChatView] Image upload response:', {
      success: uploadResponse.data.success,
      has_url: !!uploadResponse.data.data?.url,
      url: uploadResponse.data.data?.url,
      full_response: uploadResponse.data,
    });

    if (uploadResponse.data.success && uploadResponse.data.data?.url) {
      const imageUrl = uploadResponse.data.data.url; // Public URL for display
      const imagePath = uploadResponse.data.data.absolute_path; // Absolute path for zalo-service
      
      console.log('📤 [ZaloChatView] Sending message with image:', {
        account_id: accountId,
        recipient_id: props.item.id,
        recipient_type: props.itemType === 'groups' ? 'group' : 'user',
        message: textMessage || '',
        media_url: imageUrl,
        media_path: imagePath,
        content_type: 'image',
      });
      
      // Send message with image URL and optional text
      const response = await axios.post('/api/zalo/messages/send', {
        account_id: accountId,
        recipient_id: props.item.id,
        recipient_type: props.itemType === 'groups' ? 'group' : 'user',
        message: textMessage || '', // Use provided text message
        media_url: imageUrl, // Public URL for display
        media_path: imagePath, // Absolute path for zalo-service to avoid localhost download
        content_type: 'image', // CRITICAL: Must specify content_type as 'image'
      });
      
      console.log('📥 [ZaloChatView] Image send response:', {
        success: response.data.success,
        has_data: !!response.data.data,
        response_data: response.data,
      });

      if (response.data.success) {
        // IMPORTANT: Do NOT push message here (optimistic update)
        // WebSocket will receive and add the message automatically
        // This prevents duplicate messages
        
        console.log('✅ [ZaloChatView] Image sent, waiting for WebSocket');
        
        emit('message-sent');
        clearImage();
        
        // Show success toast
        Swal.fire({
          icon: 'success',
          title: t('common.success'),
          text: t('zalo.image_sent') || 'Image sent successfully',
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 2000,
        });
      } else {
        console.error('❌ [ZaloChatView] Image upload failed:', uploadResponse.data);
        // Clear image on upload failure
        clearImage();
      }
    } else {
      console.error('❌ [ZaloChatView] Image upload response not successful:', uploadResponse.data);
      // Clear image on upload failure
      clearImage();
    }
  } catch (error) {
    console.error('❌ [ZaloChatView] Failed to upload image:', {
      error: error.message,
      status: error.response?.status,
      statusText: error.response?.statusText,
      response_data: error.response?.data,
      request_config: error.config,
    });
    
    // Show user-friendly error message
    if (error.response?.data?.message) {
      console.error('❌ [ZaloChatView] Error message from server:', error.response.data.message);
    }
    
    // Clear image on error
    clearImage();
  } finally {
    uploading.value = false;
  }
};

// Clear file
const clearFile = () => {
  selectedFile.value = null;
  if (fileInput.value) fileInput.value.value = '';
};

// Clear image
const clearImage = () => {
  selectedImage.value = null;
  selectedImagePreview.value = null;
  if (imageInput.value) imageInput.value.value = '';
};

// 🔥 NEW: Mark conversation as read
const markConversationAsRead = async (recipientId) => {
  if (!currentAccountId.value || !recipientId) return;

  try {
    const branchId = localStorage.getItem('current_branch_id');
    await axios.post('/api/zalo/mark-as-read', {
      branch_id: branchId,
      account_id: currentAccountId.value,
      recipient_id: recipientId
    });

    console.log('✅ [ZaloChatView] Marked conversation as read:', recipientId);

    // Update unread count in props.item if available
    if (props.item && props.item.id === recipientId) {
      props.item.unread_count = 0;
    }
  } catch (error) {
    console.error('❌ [ZaloChatView] Error marking as read:', error);
  }
};

// Send message
const sendMessage = async () => {
  console.log('📤 [ZaloChatView] sendMessage called (Send button clicked)');
  
  if ((!messageText.value.trim() && !selectedFile.value && !selectedImage.value) || sending.value || uploading.value) {
    console.log('⚠️ [ZaloChatView] sendMessage blocked:', {
      has_text: !!messageText.value.trim(),
      has_selectedFile: !!selectedFile.value,
      has_selectedImage: !!selectedImage.value,
      sending: sending.value,
      uploading: uploading.value,
    });
    return;
  }
  
  if (!props.item?.id) {
    console.log('⚠️ [ZaloChatView] sendMessage blocked: No item.id');
    return;
  }

  console.log('📤 [ZaloChatView] sendMessage proceeding:', {
    has_text: !!messageText.value.trim(),
    text_length: messageText.value.trim().length,
    has_selectedImage: !!selectedImage.value,
    has_selectedFile: !!selectedFile.value,
    selectedImage_name: selectedImage.value?.name,
    selectedFile_name: selectedFile.value?.name,
  });

  // If there's a selected image, upload and send it with optional text
  if (selectedImage.value) {
    console.log('📤 [ZaloChatView] Image selected, NOW uploading and sending...', {
      image_name: selectedImage.value.name,
      image_size: selectedImage.value.size,
      has_text: !!messageText.value.trim(),
    });
    const text = messageText.value.trim();
    messageText.value = ''; // Clear text before upload
    await uploadImage(selectedImage.value, text); // Pass text to uploadImage
    return;
  }

  // If there's a selected file, upload and send it with optional text
  if (selectedFile.value) {
    console.log('📤 [ZaloChatView] File selected, uploading and sending...', {
      file_name: selectedFile.value.name,
      file_size: selectedFile.value.size,
      has_text: !!messageText.value.trim(),
    });
    const text = messageText.value.trim();
    messageText.value = ''; // Clear text before upload
    await uploadFile(selectedFile.value, text); // Pass text to uploadFile
    return;
  }

  // Send text message only
  console.log('📤 [ZaloChatView] Sending text message only');
  const text = messageText.value.trim();
  messageText.value = '';
  await sendTextMessage(text);
};

// Send text message (extracted for reuse)
const sendTextMessage = async (text) => {
  sending.value = true;

  // Declare variables outside try block to use in catch
  let accountId = null;
  let recipientId = null;

  try {
    accountId = currentAccountId.value;
    if (!accountId) {
      console.error('❌ [ZaloChatView] No active account');
      throw new Error('No active account');
    }

    recipientId = props.item.id || props.item.userId || props.item.groupId;
    if (!recipientId) {
      console.error('❌ [ZaloChatView] No recipient ID', props.item);
      throw new Error('No recipient ID');
    }

    // Check if message contains a URL (link)
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    const urlMatch = text.match(urlRegex);
    const hasLink = urlMatch && urlMatch.length > 0;

    const payload = {
      account_id: accountId,
      recipient_id: recipientId,
      recipient_type: props.itemType === 'groups' ? 'group' : 'user',
      message: text,
    };

    // If message contains URL, treat as link
    if (hasLink && urlMatch[0]) {
      payload.media_url = urlMatch[0];
      payload.content_type = 'link';
    }

    console.log('📤 [ZaloChatView] Sending text message:', {
      account_id: accountId,
      recipient_id: recipientId,
      recipient_type: payload.recipient_type,
      message_length: text.length,
      has_link: hasLink,
      payload,
    });

    const response = await axios.post('/api/zalo/messages/send', payload);
    
    console.log('📥 [ZaloChatView] Response received:', {
      success: response.data.success,
      message: response.data.message,
      has_data: !!response.data.data,
    });

    if (response.data.success) {
      console.log('✅ [ZaloChatView] Message sent successfully, waiting for WebSocket');
      
      // IMPORTANT: Do NOT push message here!
      // WebSocket will receive and add it automatically
      
      emit('message-sent');
    } else {
      console.error('❌ [ZaloChatView] Message send failed:', response.data);
      throw new Error(response.data.message || 'Failed to send message');
    }
  } catch (error) {
    console.error('❌ [ZaloChatView] Send message error:', {
      error: error.message,
      response: error.response?.data,
      status: error.response?.status,
      payload: {
        account_id: accountId,
        recipient_id: recipientId,
        recipient_type: props.itemType === 'groups' ? 'group' : 'user',
      },
    });
    
    messageText.value = text; // Restore message on error
    
    // Show user-friendly error message
    const errorData = error.response?.data;
    const errorMessage = errorData?.message || error.message || 'Failed to send message';
    const isCookieExpired = errorData?.details?.cookie_expired || 
                            errorData?.cookie_expired ||
                            errorMessage.toLowerCase().includes('cookie') ||
                            errorMessage.toLowerCase().includes('expired') ||
                            errorMessage.toLowerCase().includes('đăng nhập') ||
                            errorMessage.toLowerCase().includes('re-login');
    
    // Show user-friendly error message using Swal
    Swal.fire({
      icon: isCookieExpired ? 'warning' : 'error',
      title: isCookieExpired ? (t('zalo.cookie_expired') || 'Cookie Expired') : (t('common.error') || 'Error'),
      html: isCookieExpired 
        ? `<p>${errorMessage}</p><p class="mt-2 text-sm text-gray-600">${errorData?.details?.hint || 'Please re-login your Zalo account in Account Manager.'}</p>`
        : errorMessage,
      confirmButtonText: isCookieExpired 
        ? (t('zalo.go_to_account_manager') || 'Go to Account Manager')
        : (t('common.ok') || 'OK'),
    }).then((result) => {
      if (isCookieExpired && result.isConfirmed) {
        // Emit event to navigate to account manager
        window.dispatchEvent(new CustomEvent('zalo-navigate', { detail: { view: 'accounts' } }));
      }
    });
  } finally {
    sending.value = false;
  }
};

// Handle sticker selection from picker
const handleStickerSelect = async (sticker) => {
  sending.value = true;

  // Declare variables outside try block to use in catch
  let accountId = null;
  let recipientId = null;

  try {
    accountId = currentAccountId.value;
    if (!accountId) {
      console.error('❌ [ZaloChatView] No active account');
      throw new Error('No active account');
    }

    recipientId = props.item.id || props.item.userId || props.item.groupId;
    if (!recipientId) {
      console.error('❌ [ZaloChatView] No recipient ID', props.item);
      throw new Error('No recipient ID');
    }

    console.log('🎨 [ZaloChatView] Sending sticker:', {
      account_id: accountId,
      recipient_id: recipientId,
      sticker,
    });

    // Step 1: Send sticker via zalo-service
    const zaloServiceUrl = import.meta.env.VITE_ZALO_SERVICE_URL;
    const apiKey = import.meta.env.VITE_ZALO_SERVICE_API_KEY;

    const stickerResponse = await axios.post(
      `${zaloServiceUrl}/api/message/send-sticker`,
      {
        to: recipientId,
        sticker: {
          id: sticker.id,
          cateId: sticker.cateId,
          type: sticker.type,
        },
        type: props.itemType === 'groups' ? 'group' : 'user',
      },
      {
        headers: {
          'X-API-Key': apiKey,
          'X-Account-Id': accountId,
        },
      }
    );

    console.log('✅ [ZaloChatView] Sticker sent successfully via zalo-service:', stickerResponse.data);
    console.log('ℹ️ [ZaloChatView] WebSocket will handle saving message and recording recent sticker');

    // Close sticker picker
    showStickerPicker.value = false;

    // Emit message sent event
    emit('message-sent');
  } catch (error) {
    console.error('❌ [ZaloChatView] Send sticker error:', {
      error: error.message,
      response: error.response?.data,
      status: error.response?.status,
    });

    // Show user-friendly error message
    const errorData = error.response?.data;
    const errorMessage = errorData?.message || error.message || 'Failed to send sticker';
    const isCookieExpired =
      errorData?.details?.cookie_expired ||
      errorData?.cookie_expired ||
      errorMessage.toLowerCase().includes('cookie') ||
      errorMessage.toLowerCase().includes('expired') ||
      errorMessage.toLowerCase().includes('đăng nhập') ||
      errorMessage.toLowerCase().includes('re-login');

    Swal.fire({
      icon: isCookieExpired ? 'warning' : 'error',
      title: isCookieExpired
        ? t('zalo.cookie_expired') || 'Cookie Expired'
        : t('common.error') || 'Error',
      html: isCookieExpired
        ? `<p>${errorMessage}</p><p class="mt-2 text-sm text-gray-600">${
            errorData?.details?.hint || 'Please re-login your Zalo account in Account Manager.'
          }</p>`
        : errorMessage,
      confirmButtonText: isCookieExpired
        ? t('zalo.go_to_account_manager') || 'Go to Account Manager'
        : t('common.ok') || 'OK',
    }).then((result) => {
      if (isCookieExpired && result.isConfirmed) {
        // Emit event to navigate to account manager
        window.dispatchEvent(new CustomEvent('zalo-navigate', { detail: { view: 'accounts' } }));
      }
    });
  } finally {
    sending.value = false;
  }
};

// Scroll to bottom
const scrollToBottom = (smooth = false) => {
  if (messagesContainer.value) {
    if (smooth) {
      messagesContainer.value.scrollTo({
        top: messagesContainer.value.scrollHeight,
        behavior: 'smooth'
      });
    } else {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
  }
};

// Scroll to quoted message
const scrollToQuotedMessage = async (messageId) => {
  if (!messageId) return;
  
  console.log('🔍 [ZaloChatView] Scrolling to quoted message:', messageId);
  
  // Wait for next tick to ensure DOM is updated
  await nextTick();
  
  // Try to find message by ID in current messages list
  const messageElement = document.getElementById(`message-${messageId}`);
  
  if (messageElement && messagesContainer.value) {
    // Message is in current view, scroll to it
    messageElement.scrollIntoView({ 
      behavior: 'smooth', 
      block: 'center',
      inline: 'nearest'
    });
    
    // Highlight the message briefly
    messageElement.classList.add('ring-2', 'ring-blue-500', 'ring-opacity-50');
    setTimeout(() => {
      messageElement.classList.remove('ring-2', 'ring-blue-500', 'ring-opacity-50');
    }, 2000);
    
    console.log('✅ [ZaloChatView] Scrolled to message:', messageId);
  } else {
    // Message not in current view, try to load more messages
    console.log('⚠️  [ZaloChatView] Message not found in current view, attempting to load...');
    
    // Check if message exists in database by loading messages with a filter
    try {
      const accountId = currentAccountId.value;
      if (!accountId) return;
      
      // Load messages and find the one we're looking for
      const response = await axios.get('/api/zalo/messages', {
        params: {
          account_id: accountId,
          recipient_id: props.item.id,
          recipient_type: recipientType.value,
          per_page: 200, // Load more messages to find the quoted one
        },
      });
      
      if (response.data.success) {
        messages.value = response.data.data || [];
        
        // Wait for DOM update
        await nextTick();
        
        // Try to find and scroll to the message again
        const foundElement = document.getElementById(`message-${messageId}`);
        if (foundElement && messagesContainer.value) {
          foundElement.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center',
            inline: 'nearest'
          });
          
          // Highlight the message briefly
          foundElement.classList.add('ring-2', 'ring-blue-500', 'ring-opacity-50');
          setTimeout(() => {
            foundElement.classList.remove('ring-2', 'ring-blue-500', 'ring-opacity-50');
          }, 2000);
          
          console.log('✅ [ZaloChatView] Found and scrolled to message after loading:', messageId);
        } else {
          console.warn('⚠️  [ZaloChatView] Message still not found after loading:', messageId);
        }
      }
    } catch (error) {
      console.error('❌ [ZaloChatView] Failed to load messages for scroll:', error);
    }
  }
};

// Format time
const formatTime = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diff = now - date;
  const minutes = Math.floor(diff / 60000);
  
  if (minutes < 1) return t('zalo.just_now');
  if (minutes < 60) return `${minutes}${t('zalo.minutes_ago')}`;
  if (date.toDateString() === now.toDateString()) {
    return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
  }
  return date.toLocaleString('vi-VN');
};

// Open media
const openMedia = (url) => {
  window.open(url, '_blank');
};

// Start reply
const startReply = (message) => {
  replyingTo.value = {
    id: message.id,
    message_id: message.message_id,
    content: message.content,
    sender_name: message.type === 'sent' ? 'You' : message.recipient_name,
    type: message.type,
  };
  replyText.value = '';
  // Scroll messages container to bottom to show reply input
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTo({
        top: messagesContainer.value.scrollHeight,
        behavior: 'smooth'
      });
    }

    // Focus on reply textarea after scroll animation
    setTimeout(() => {
      const textarea = document.querySelector('textarea[placeholder*="reply"]');
      if (textarea) {
        textarea.focus();
      }
    }, 300);
  });
};

// Cancel reply
const cancelReply = () => {
  replyingTo.value = null;
  replyText.value = '';
};

// Send reply
const sendReply = async () => {
  if (!replyText.value.trim() || sendingReply.value || !replyingTo.value) return;
  if (!props.item?.id) return;

  sendingReply.value = true;
  const text = replyText.value.trim();
  replyText.value = '';

  try {
    const accountId = currentAccountId.value;
    if (!accountId) {
      throw new Error('No active account');
    }

    const recipientId = props.item.id || props.item.userId || props.item.groupId;
    if (!recipientId) {
      throw new Error('No recipient ID');
    }

    const payload = {
      account_id: accountId,
      recipient_id: recipientId,
      recipient_type: props.itemType === 'groups' ? 'group' : 'user',
      message: text,
      reply_to_message_id: replyingTo.value.id,
      reply_to_zalo_message_id: replyingTo.value.message_id,
    };

    console.log('📤 [ZaloChatView] Sending reply:', payload);

    const response = await axios.post('/api/zalo/messages/reply', payload);

    if (response.data.success) {
      console.log('✅ [ZaloChatView] Reply sent successfully, waiting for WebSocket');
      
      // IMPORTANT: Do NOT push message here!
      // WebSocket will receive and add it automatically
      
      cancelReply();
      emit('message-sent');
    } else {
      throw new Error(response.data.message || 'Failed to send reply');
    }
  } catch (error) {
    console.error('❌ [ZaloChatView] Send reply error:', error);
    replyText.value = text; // Restore text on error
    
    Swal.fire({
      icon: 'error',
      title: t('common.error') || 'Error',
      text: error.response?.data?.message || error.message || 'Failed to send reply',
    });
  } finally {
    sendingReply.value = false;
  }
};

// Toggle reaction picker
const toggleReactionPicker = (messageId) => {
  console.log('🎯 [ZaloChatView] Toggle reaction picker:', {
    messageId,
    current: showReactionPickerFor.value,
    availableReactions: availableReactions.length,
  });
  
  if (showReactionPickerFor.value === messageId) {
    showReactionPickerFor.value = null;
  } else {
    showReactionPickerFor.value = messageId;
    // Close any other open pickers
    nextTick(() => {
      console.log('✅ [ZaloChatView] Reaction picker opened for message:', messageId);
    });
  }
};

// Add reaction
const addReaction = async (message, reactionIcon) => {
  if (!message.id) return;
  
  try {
    const accountId = currentAccountId.value;
    if (!accountId) {
      throw new Error('No active account');
    }

    const recipientId = props.item.id || props.item.userId || props.item.groupId;
    if (!recipientId) {
      throw new Error('No recipient ID');
    }

    // Get cliMsgId from message metadata if available
    const cliMsgId = message.metadata?.cliMsgId || message.message_id;

    const payload = {
      account_id: accountId,
      message_id: message.id,
      zalo_message_id: message.message_id,
      cli_msg_id: cliMsgId,
      recipient_id: recipientId,
      recipient_type: props.itemType === 'groups' ? 'group' : 'user',
      reaction: reactionIcon,
    };

    console.log('😊 [ZaloChatView] Adding reaction:', {
      ...payload,
      message_metadata: message.metadata,
    });

    const response = await axios.post('/api/zalo/messages/reaction', payload);

    if (response.data.success) {
      console.log('✅ [ZaloChatView] Reaction added successfully');
      
      // Reload reactions for this message
      await loadReactions(message.id);
      
      // Close picker
      showReactionPickerFor.value = null;
    } else {
      throw new Error(response.data.message || 'Failed to add reaction');
    }
  } catch (error) {
    console.error('❌ [ZaloChatView] Add reaction error:', error);
    Swal.fire({
      icon: 'error',
      title: t('common.error') || 'Error',
      text: error.response?.data?.message || error.message || 'Failed to add reaction',
    });
  }
};

// Load reactions for a message
const loadReactions = async (messageId) => {
  try {
    const accountId = currentAccountId.value;
    if (!accountId) return;

    const response = await axios.get(`/api/zalo/messages/${messageId}/reactions`, {
      params: { account_id: accountId },
    });

    if (response.data.success) {
      // Update reactions for this message
      const message = messages.value.find(m => m.id === messageId);
      if (message) {
        // Map the response data to match our format
        message.reactions = (response.data.data || []).map(reaction => ({
          reaction: reaction.reaction,
          count: reaction.count,
          users: reaction.users || [],
        }));
      }
    }
  } catch (error) {
    console.error('Failed to load reactions:', error);
  }
};

// Get reaction emoji
const getReactionEmoji = (reactionIcon) => {
  const reaction = availableReactions.find(r => r.icon === reactionIcon);
  if (reaction) return reaction.emoji;
  
  // Fallback mapping for common Zalo reaction icons
  const iconMap = {
    '/-heart': '❤️',
    '/-strong': '👍',
    ':>': '😂',
    ':o': '😮',
    ':-((': '😢',
    ':-h': '😠',
  };
  
  return iconMap[reactionIcon] || '👍';
};

// Show reaction users
const showReactionUsers = (messageId, reaction) => {
  const users = reaction.users || [];
  if (users.length === 0) return;
  
  Swal.fire({
    icon: 'info',
    title: `${reaction.count} ${getReactionEmoji(reaction.reaction)}`,
    text: users.join(', '),
    confirmButtonText: t('common.ok') || 'OK',
  });
};

// Watch item changes (conversation switch)
// This ONLY runs when switching conversations, NOT on initial mount
watch(() => props.item, (newItem, oldItem) => {
  console.log('👁️ [ZaloChatView] props.item changed:', {
    newItemId: newItem?.id,
    oldItemId: oldItem?.id,
    isSameItem: newItem?.id === oldItem?.id,
  });

  // Skip if same item (prevent duplicate on mount)
  if (newItem?.id === oldItem?.id) {
    console.log('⏭️ [ZaloChatView] Skipping watch - same item ID');
    return;
  }

  // Skip if oldItem is undefined (this is initial mount, handled by onMounted)
  if (!oldItem || !oldItem.id) {
    console.log('⏭️ [ZaloChatView] Initial mount detected in watch, skipping (onMounted will handle)');
    return;
  }

  // Skip if newItem is invalid
  if (!newItem || !newItem.id) {
    console.warn('⚠️ [ZaloChatView] New item is invalid, skipping');
    return;
  }

  // 🔥 CRITICAL FIX: Clear messages immediately to prevent showing old conversation's messages
  console.log('🧹 [ZaloChatView] Clearing messages before switching conversation');
  messages.value = [];
  loadingMessages.value = true; // Show loading immediately

  // Leave old conversation room
  if (oldItem?.id && currentAccountId.value) {
    console.log('👋 [ZaloChatView] Leaving old conversation:', oldItem.id);
    zaloSocket.leaveConversation(currentAccountId.value, oldItem.id);
  }

  // Join new conversation room and load messages
  if (newItem?.id && currentAccountId.value) {
    console.log('👋 [ZaloChatView] Joining new conversation:', newItem.id);
    zaloSocket.joinConversation(currentAccountId.value, newItem.id);

    // Load messages (handles async internally and clears loading state)
    loadMessages().catch(error => {
      console.error('❌ [ZaloChatView] Error loading messages in watch:', error);
      loadingMessages.value = false; // Clear loading state on error
    });

    // 🔥 NEW: Mark conversation as read when joining
    if (newItem.unread_count && newItem.unread_count > 0) {
      console.log('📖 [ZaloChatView] Marking conversation as read:', newItem.id, 'unread:', newItem.unread_count);
      markConversationAsRead(newItem.id);
    }
  } else {
    // If we can't load (no accountId), clear loading state
    console.warn('⚠️ [ZaloChatView] Cannot load messages - missing accountId or newItem.id');
    loadingMessages.value = false;
  }
});

// Close reaction picker when clicking outside
const handleClickOutside = (event) => {
  if (showReactionPickerFor.value && !event.target.closest('.reaction-picker-container')) {
    showReactionPickerFor.value = null;
  }
};

onMounted(() => {
  console.log('🔵 [ZaloChatView] Component mounted for:', props.item?.id);

  // Connect to WebSocket
  zaloSocket.connect();

  // Wait for WebSocket to connect before joining rooms
  const checkConnectionAndJoin = () => {
    if (zaloSocket.isConnected.value) {
      console.log('✅ [ZaloChatView] WebSocket connected, joining rooms...');

      // Join account room for conversation updates
      if (currentAccountId.value) {
        zaloSocket.joinAccount(currentAccountId.value);
      }

      // Join conversation room if item is selected
      if (props.item?.id && currentAccountId.value) {
        zaloSocket.joinConversation(currentAccountId.value, props.item.id);

        // 🔥 NEW: Mark conversation as read when joining on mount
        if (props.item.unread_count && props.item.unread_count > 0) {
          console.log('📖 [ZaloChatView onMounted] Marking conversation as read:', props.item.id, 'unread:', props.item.unread_count);
          markConversationAsRead(props.item.id);
        }
      }
    } else {
      console.log('⏳ [ZaloChatView] Waiting for WebSocket to connect...');
      // Retry after a short delay
      setTimeout(checkConnectionAndJoin, 100);
    }
  };

  // Start checking for connection
  checkConnectionAndJoin();

  // 🐛 DEBUG: Add raw socket listener to see ALL events
  const rawSocket = zaloSocket.socket();
  if (rawSocket) {
    console.log('🐛 [ZaloChatView] Adding raw socket listener for debugging...');
    rawSocket.on('zalo:message:new', (data) => {
      console.log('🐛 [ZaloChatView] RAW socket event received!', data);
    });
  }

  // Listen for new messages (ONLY ONCE!)
  const unsubscribeMessage = zaloSocket.onMessage((data) => {
    console.log('📨 [ZaloChatView] onMessage triggered', {
      account_match: data.account_id === currentAccountId.value,
      recipient_match: data.recipient_id === props.item?.id,
      message_id: data.message?.id,
      already_exists: data.message ? messages.value.find(m => m.id === data.message.id) : null,
    });

    if (data.account_id === currentAccountId.value &&
        data.recipient_id === props.item?.id) {
      // Add new message to list
      const newMessage = data.message;
      if (newMessage && !messages.value.find(m => m.id === newMessage.id)) {
        console.log('✅ [ZaloChatView] Adding new message to UI:', newMessage.id);
        messages.value.push(newMessage);

        // Update cache with new message
        const conversationId = props.item.id;
        if (messagesCache.has(conversationId)) {
          const cachedMessages = messagesCache.get(conversationId);
          if (!cachedMessages.find(m => m.id === newMessage.id)) {
            messagesCache.set(conversationId, [...cachedMessages, newMessage]);
            console.log('💾 [ZaloChatView] Updated cache with new message for:', conversationId);
          }
        }

        nextTick(() => {
          setTimeout(() => {
            scrollToBottom(true); // Smooth scroll for new messages
          }, 50);
        });
      } else {
        console.warn('⚠️ [ZaloChatView] Message already exists or invalid:', {
          has_message: !!newMessage,
          message_id: newMessage?.id,
          exists: newMessage ? messages.value.find(m => m.id === newMessage.id) : null,
        });
      }
    }
  });
  
  // Listen for new reactions
  const unsubscribeReaction = zaloSocket.onReaction((data) => {
    if (data.account_id === currentAccountId.value && 
        data.recipient_id === props.item?.id) {
      // Reload reactions for the message
      if (data.message_id) {
        loadReactions(data.message_id);
      }
    }
  });
  
  // Listen for conversation updates
  const unsubscribeConversation = zaloSocket.onConversationUpdate((data) => {
    // Don't reload all messages - just update the conversation list
    // The new message will be added via onMessage listener
    // This prevents screen blinking
    if (data.account_id === currentAccountId.value && 
        data.recipient_id === props.item?.id) {
      // Only update if we're not already showing this conversation
      // The message will be added via onMessage listener above
      console.log('📬 [ZaloChatView] Conversation updated, message will be added via onMessage');
    }
  });
  
  // Add click outside listener
  document.addEventListener('click', handleClickOutside);

  // Attach scroll event listener for lazy loading
  if (messagesContainer.value) {
    messagesContainer.value.addEventListener('scroll', handleScroll);
    console.log('📜 [ZaloChatView] Scroll event listener attached');
  }

  // Load initial messages (ONCE!)
  loadMessages();
  
  // Cleanup on unmount
  onUnmounted(() => {
    console.log('🔴 [ZaloChatView] Component unmounted for:', props.item?.id);

    document.removeEventListener('click', handleClickOutside);

    // Remove scroll event listener
    if (messagesContainer.value) {
      messagesContainer.value.removeEventListener('scroll', handleScroll);
      console.log('📜 [ZaloChatView] Scroll event listener removed');
    }

    // Unsubscribe from all listeners
    if (unsubscribeMessage) unsubscribeMessage();
    if (unsubscribeConversation) unsubscribeConversation();
    if (unsubscribeReaction) unsubscribeReaction();
    
    // Leave rooms
    if (props.item?.id && currentAccountId.value) {
      zaloSocket.leaveConversation(currentAccountId.value, props.item.id);
    }

    if (currentAccountId.value) {
      zaloSocket.leaveAccount(currentAccountId.value);
    }
  });
});
</script>

<style scoped>
/* ULTIMATE FIX: Complete CSS reset for Zalo images */
.zalo-image-container {
  all: unset !important;
  display: block !important;
  margin-top: 8px !important;
  cursor: pointer !important;
  position: relative !important;
  z-index: 1 !important;
  background: transparent !important;
}

.zalo-image-preview {
  all: unset !important;
  display: block !important;
  max-width: 250px !important;
  max-height: 250px !important;
  width: auto !important;
  height: auto !important;
  visibility: visible !important;
  opacity: 1 !important;
  position: relative !important;
  z-index: 1 !important;
  border-radius: 8px !important;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
  background: white !important;
  object-fit: contain !important;
  filter: none !important;
  transform: none !important;
  mix-blend-mode: normal !important;
}

/* Override for ANY Zalo CDN image */
img[src*="zdn.vn"],
img[src*="dlfl.vn"] {
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
  background: white !important;
  filter: none !important;
}
</style>
