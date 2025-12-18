import { ref, reactive } from 'vue';

// Global toast state
const toasts = ref([]);
let toastId = 0;

// Audio context for notification sound
let audioContext = null;
let audioInitialized = false;

/**
 * Initialize AudioContext on user interaction (required by browser policy)
 * This must be called from a user gesture (click, keydown, etc.)
 */
const initAudioContext = () => {
  if (audioInitialized) return;

  try {
    audioContext = new (window.AudioContext || window.webkitAudioContext)();
    audioInitialized = true;
    console.log('🔊 [Toast] AudioContext initialized');
  } catch (e) {
    console.log('Could not initialize AudioContext:', e);
  }
};

// Auto-initialize AudioContext on first user interaction
if (typeof window !== 'undefined') {
  const initOnInteraction = () => {
    initAudioContext();
    // Remove listeners after initialization
    document.removeEventListener('click', initOnInteraction);
    document.removeEventListener('keydown', initOnInteraction);
    document.removeEventListener('touchstart', initOnInteraction);
  };

  document.addEventListener('click', initOnInteraction, { once: true });
  document.addEventListener('keydown', initOnInteraction, { once: true });
  document.addEventListener('touchstart', initOnInteraction, { once: true });
}

/**
 * Play notification sound using Web Audio API
 */
const playNotificationSound = () => {
  try {
    // Try to initialize if not already (won't work without user interaction)
    if (!audioContext) {
      initAudioContext();
    }

    if (!audioContext) {
      console.log('🔇 [Toast] AudioContext not available (no user interaction yet)');
      return;
    }

    // Resume if suspended (browser policy)
    if (audioContext.state === 'suspended') {
      audioContext.resume();
    }

    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);

    // Create a pleasant notification tone
    oscillator.frequency.setValueAtTime(830, audioContext.currentTime); // First tone
    oscillator.frequency.setValueAtTime(1046, audioContext.currentTime + 0.1); // Second tone (higher)

    // Volume envelope
    gainNode.gain.setValueAtTime(0, audioContext.currentTime);
    gainNode.gain.linearRampToValueAtTime(0.3, audioContext.currentTime + 0.02);
    gainNode.gain.linearRampToValueAtTime(0.2, audioContext.currentTime + 0.1);
    gainNode.gain.linearRampToValueAtTime(0.3, audioContext.currentTime + 0.12);
    gainNode.gain.linearRampToValueAtTime(0, audioContext.currentTime + 0.3);

    oscillator.type = 'sine';
    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + 0.3);

    console.log('🔊 [Toast] Notification sound played');
  } catch (e) {
    console.log('Could not play notification sound:', e);
  }
};

export function useToast() {
  /**
   * Add a new toast notification
   * @param {Object} options - Toast options
   * @param {string} options.title - Toast title
   * @param {string} options.message - Toast message
   * @param {string} options.type - Toast type: 'info', 'success', 'warning', 'error', 'zalo'
   * @param {number} options.duration - Duration in ms (default: 5000, 0 = no auto-dismiss)
   * @param {string} options.avatar - Avatar URL (for zalo type)
   * @param {Function} options.onClick - Click handler
   */
  const addToast = (options) => {
    const id = ++toastId;
    const toast = {
      id,
      title: options.title || '',
      message: options.message || '',
      type: options.type || 'info',
      duration: options.duration !== undefined ? options.duration : 5000,
      avatar: options.avatar || null,
      onClick: options.onClick || null,
      createdAt: Date.now(),
    };

    toasts.value.push(toast);

    // Auto-dismiss
    if (toast.duration > 0) {
      setTimeout(() => {
        removeToast(id);
      }, toast.duration);
    }

    return id;
  };

  /**
   * Remove a toast by ID
   */
  const removeToast = (id) => {
    const index = toasts.value.findIndex(t => t.id === id);
    if (index > -1) {
      toasts.value.splice(index, 1);
    }
  };

  /**
   * Clear all toasts
   */
  const clearAll = () => {
    toasts.value = [];
  };

  /**
   * Show Zalo message toast with notification sound
   */
  const showZaloMessage = ({ senderName, message, avatar, onClick }) => {
    // Play notification sound
    playNotificationSound();

    return addToast({
      title: senderName,
      message: message,
      type: 'zalo',
      avatar: avatar,
      duration: 6000,
      onClick,
    });
  };

  /**
   * Show success toast
   */
  const success = (message, title = '') => {
    return addToast({ title, message, type: 'success' });
  };

  /**
   * Show error toast
   */
  const error = (message, title = '') => {
    return addToast({ title, message, type: 'error' });
  };

  /**
   * Show info toast
   */
  const info = (message, title = '') => {
    return addToast({ title, message, type: 'info' });
  };

  /**
   * Show warning toast
   */
  const warning = (message, title = '') => {
    return addToast({ title, message, type: 'warning' });
  };

  return {
    toasts,
    addToast,
    removeToast,
    clearAll,
    showZaloMessage,
    success,
    error,
    info,
    warning,
  };
}
