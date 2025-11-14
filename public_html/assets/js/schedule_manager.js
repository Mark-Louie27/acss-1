// Centralized Schedule Manager for real-time updates
class ScheduleManager {
  constructor() {
    this.scheduleData = [];
    this.listeners = [];
    this.isInitialized = false;
  }

  // Initialize with initial data
  initialize(initialData = []) {
    this.scheduleData = Array.isArray(initialData) ? initialData : [];
    this.isInitialized = true;
    console.log(
      "📋 Schedule Manager initialized with",
      this.scheduleData.length,
      "schedules"
    );
    this.notifyListeners();
  }

  // Get all schedules
  getSchedules() {
    return this.scheduleData;
  }

  // Update schedules
  setSchedules(newSchedules) {
    this.scheduleData = Array.isArray(newSchedules) ? newSchedules : [];
    console.log("🔄 Schedules updated:", this.scheduleData.length, "schedules");
    this.notifyListeners();

    // Also update the global variable for backward compatibility
    if (window.scheduleData) {
      window.scheduleData = this.scheduleData;
    }
  }

  // Add a single schedule
  addSchedule(schedule) {
    this.scheduleData.push(schedule);
    console.log("➕ Schedule added:", schedule);
    this.notifyListeners();
  }

  // Update a single schedule
  updateSchedule(scheduleId, updatedSchedule) {
    const index = this.scheduleData.findIndex(
      (s) => s.schedule_id == scheduleId
    );
    if (index !== -1) {
      this.scheduleData[index] = {
        ...this.scheduleData[index],
        ...updatedSchedule,
      };
      console.log("✏️ Schedule updated:", scheduleId);
      this.notifyListeners();
    }
  }

  // Delete a single schedule
  deleteSchedule(scheduleId) {
    this.scheduleData = this.scheduleData.filter(
      (s) => s.schedule_id != scheduleId
    );
    console.log("🗑️ Schedule deleted:", scheduleId);
    this.notifyListeners();
  }

  // Clear all schedules
  clearAll() {
    this.scheduleData = [];
    console.log("🧹 All schedules cleared");
    this.notifyListeners();
  }

  // Register listener for schedule updates
  addListener(callback) {
    this.listeners.push(callback);
    return () => {
      this.listeners = this.listeners.filter(
        (listener) => listener !== callback
      );
    };
  }

  // Notify all listeners
  notifyListeners() {
    this.listeners.forEach((listener) => {
      try {
        listener(this.scheduleData);
      } catch (error) {
        console.error("Error in schedule listener:", error);
      }
    });
  }

  // Check if schedules exist
  hasSchedules() {
    return this.scheduleData.length > 0;
  }

  // Get schedule count
  getScheduleCount() {
    return this.scheduleData.length;
  }
}

// Create global instance
window.scheduleManager = new ScheduleManager();
