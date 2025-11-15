// Debug logging at the beginning of the script
console.log("=== GENERATE SCHEDULES DEBUG ===");
console.log("Raw data received:", window.jsData);
console.log("Sections data:", window.rawSectionsData);
console.log("Faculty data:", window.faculty);
console.log("Classrooms data:", window.classrooms);
console.log("Curricula data:", window.curricula);
console.log(
  "Curriculum courses for current semester:",
  window.jsData?.curriculumCourses || []
);
console.log("Current semester:", window.currentSemester);
console.log("Department ID:", window.departmentId);

// Initialize data
function initializeScheduleData() {
  window.sectionsData = Array.isArray(window.rawSectionsData)
    ? window.rawSectionsData.map((s, index) => ({
        section_id: s.section_id ?? index + 1,
        section_name: s.section_name ?? "",
        year_level: s.year_level ?? "Unknown",
        academic_year: s.academic_year ?? window.currentAcademicYear,
        current_students: s.current_students ?? 0,
        max_students: s.max_students ?? 30,
        semester: s.semester ?? "",
        is_active: s.is_active ?? 1,
        curriculum_id: s.curriculum_id || null,
      }))
    : [];

  console.log("Processed sections data:", window.sectionsData);

  if (window.sectionsData.length === 0) {
    console.warn("No sections found for the current semester.");
    showValidationToast([
      "No sections found for the current semester. Please ensure sections are added in the database.",
    ]);
  }

  window.curriculumCourses = Array.isArray(window.jsData?.curriculumCourses)
    ? window.jsData.curriculumCourses.map((c, index) => ({
        course_id: c.course_id ?? index + 1,
        course_code: c.course_code ?? "",
        course_name: c.course_name ?? "Unknown",
        year_level: c.curriculum_year ?? "Unknown",
        semester:
          c.curriculum_semester ?? window.currentSemester?.semester_name,
        subject_type: c.subject_type ?? "",
        units: c.units ?? 0,
        lecture_units: c.lecture_units ?? 0,
        lab_units: c.lab_units ?? 0,
        lecture_hours: c.lecture_hours ?? 0,
        lab_hours: c.lab_hours ?? 0,
      }))
    : [];

  console.log("Processed curriculum courses:", window.curriculumCourses);

  if (window.curriculumCourses.length === 0) {
    const coursesList = document.getElementById("courses-list");
    if (coursesList) {
      coursesList.innerHTML =
        '<p class="text-sm text-gray-600">Please select a curriculum to view available courses.</p>';
    }
  }
}

// Helper function to escape HTML
function escapeHtml(unsafe) {
  if (!unsafe) return "";
  return unsafe
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

// Helper function to get or create toast container
function getOrCreateToastContainer() {
  let toastContainer = document.getElementById("toast-container");
  if (!toastContainer) {
    toastContainer = document.createElement("div");
    toastContainer.id = "toast-container";
    toastContainer.className = "fixed top-4 right-4 z-50 space-y-2";
    document.body.appendChild(toastContainer);
  }
  return toastContainer;
}

// Show validation error toast
function showValidationToast(errors) {
  const toastContainer = getOrCreateToastContainer();

  const toast = document.createElement("div");
  toast.className =
    "bg-red-50 border border-red-200 rounded-lg p-4 shadow-lg max-w-sm w-full transition-opacity duration-300";
  toast.innerHTML = `
    <div class="flex items-start">
      <div class="flex-shrink-0">
        <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
      </div>
      <div class="ml-3 flex-1">
        <p class="text-sm font-medium text-red-800">Validation Error</p>
        <ul class="list-disc pl-5 text-sm text-red-700 mt-1">
          ${errors.map((error) => `<li>${escapeHtml(error)}</li>`).join("")}
        </ul>
      </div>
      <div class="ml-3 flex-shrink-0">
        <button class="text-red-400 hover:text-red-600" onclick="this.parentElement.parentElement.parentElement.remove()">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
  `;

  toastContainer.appendChild(toast);
  setTimeout(() => {
    toast.classList.add("opacity-0");
    setTimeout(() => toast.remove(), 300);
  }, 5000);
}

// Show completion toast (success or warning)
function showCompletionToast(type, title, messages) {
  const toastContainer = getOrCreateToastContainer();

  const toast = document.createElement("div");
  toast.className = `bg-${
    type === "success" ? "green" : "yellow"
  }-50 border border-${
    type === "success" ? "green" : "yellow"
  }-200 rounded-lg p-4 shadow-lg max-w-sm w-full transition-opacity duration-300`;
  toast.innerHTML = `
    <div class="flex items-start">
      <div class="flex-shrink-0">
        <i class="fas ${
          type === "success"
            ? "fa-check-circle text-green-500"
            : "fa-exclamation-triangle text-yellow-500"
        } text-xl"></i>
      </div>
      <div class="ml-3 flex-1">
        <p class="text-sm font-medium ${
          type === "success" ? "text-green-800" : "text-yellow-800"
        }">${escapeHtml(title)}</p>
        <ul class="list-disc pl-5 text-sm ${
          type === "success" ? "text-green-700" : "text-yellow-700"
        } mt-1">
          ${messages.map((msg) => `<li>${escapeHtml(msg)}</li>`).join("")}
        </ul>
      </div>
      <div class="ml-3 flex-shrink-0">
        <button class="${
          type === "success"
            ? "text-green-400 hover:text-green-600"
            : "text-yellow-400 hover:text-yellow-600"
        }" onclick="this.parentElement.parentElement.parentElement.remove()">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
  `;

  toastContainer.appendChild(toast);
  setTimeout(() => {
    toast.classList.add("opacity-0");
    setTimeout(() => toast.remove(), 300);
  }, 5000);
}

// Highlight invalid form fields
function highlightField(fieldId, hasError) {
  const field = document.getElementById(fieldId);
  if (field) {
    if (hasError) {
      field.classList.add("border-red-500", "ring-2", "ring-red-500");
    } else {
      field.classList.remove("border-red-500", "ring-2", "ring-red-500");
      field.classList.add("border-gray-300");
    }
  }
}

// Clear validation errors
function clearValidationErrors() {
  ["curriculum_id"].forEach((fieldId) => highlightField(fieldId, false));
}

// Update courses list based on selected curriculum
function updateCourses() {
  const curriculumId = document.getElementById("curriculum_id").value;
  const coursesList = document.getElementById("courses-list");
  if (!coursesList) {
    console.error("Courses list element not found");
    return;
  }
  console.log("updateCourses called with curriculum:", curriculumId);
  if (!curriculumId) {
    coursesList.innerHTML =
      '<p class="text-sm text-gray-600">Please select a curriculum to view available courses.</p>';
    return;
  }
  coursesList.innerHTML =
    '<p class="text-sm text-gray-600">Loading courses...</p>';

  fetch("/chair/generate-schedules", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({
      action: "get_curriculum_courses",
      curriculum_id: curriculumId,
      semester_id: window.currentSemester.semester_id,
      department_id: window.departmentId,
      college_id: window.jsData.collegeId,
    }),
  })
    .then((response) => {
      if (!response.ok)
        throw new Error(`HTTP error! status: ${response.status}`);
      return response.text();
    })
    .then((text) => {
      console.log("Raw response:", text);
      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        console.error("JSON parse error:", e, "Response:", text);
        throw new Error("Invalid JSON response: " + e.message);
      }
      console.log("Fetched courses:", data.courses);
      window.curriculumCourses = data.courses || [];
      if (window.curriculumCourses.length === 0) {
        coursesList.innerHTML =
          '<p class="text-sm text-red-600">No courses found for the selected curriculum and semester.</p>';
      } else {
        coursesList.innerHTML = `
                <ul class="list-disc pl-5 text-sm text-gray-700">
                    ${window.curriculumCourses
                      .map(
                        (course) => `
                                <li>
                                    ${escapeHtml(
                                      course.course_code
                                    )} - ${escapeHtml(course.course_name)}
                                    (Year: ${escapeHtml(
                                      course.curriculum_year
                                    )}, Semester: ${escapeHtml(
                          course.curriculum_semester
                        )})
                                </li>
                            `
                      )
                      .join("")}
                </ul>
            `;
      }
    })
    .catch((error) => {
      console.error("Error fetching courses:", error);
      coursesList.innerHTML =
        '<p class="text-sm text-red-600">Error loading courses. Please try again.</p>';
      showValidationToast(["Error loading courses: " + error.message]);
    });
}

// Update schedule completion status banner
function updateScheduleCompletionStatus(data) {
  let statusBanner = document.getElementById("schedule-completion-banner");

  if (statusBanner) {
    statusBanner.remove();
  }

  if (data.unassignedCourses && data.unassignedCourses.length > 0) {
    const navTabs = document.querySelector("nav.flex.space-x-1");
    statusBanner = document.createElement("div");
    statusBanner.id = "schedule-completion-banner";
    statusBanner.className =
      "mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded-lg shadow-sm";

    statusBanner.innerHTML = `
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
        </div>
        <div class="ml-3 flex-1">
          <h3 class="text-sm font-semibold text-yellow-800">Schedule Generation Incomplete</h3>
          <div class="mt-2 text-sm text-yellow-700">
            <p class="mb-2">${
              data.unassignedCourses.length
            } course(s) could not be scheduled automatically:</p>
            <ul class="list-disc list-inside ml-2">
              ${data.unassignedCourses
                .map((c) => `<li>${escapeHtml(c.course_code)}</li>`)
                .join("")}
            </ul>
            <p class="mt-3">
              <strong>Success Rate:</strong> ${data.successRate || "0%"} 
              (${data.totalCourses - data.unassignedCourses.length} of ${
      data.totalCourses
    } courses scheduled)
            </p>
          </div>
          <div class="mt-3">
            <button onclick="switchTab('manual')" class="text-sm font-medium text-yellow-800 hover:text-yellow-900 underline">
              Go to Manual Edit to fix conflicts →
            </button>
          </div>
        </div>
        <div class="flex-shrink-0 ml-3">
          <button onclick="this.parentElement.parentElement.parentElement.remove()" class="text-yellow-600 hover:text-yellow-800">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
    `;

    if (navTabs && navTabs.parentElement) {
      navTabs.parentElement.insertBefore(statusBanner, navTabs.nextSibling);
    }
  }
}

// FIXED: Main generate schedules function with proper async handling
function generateSchedules() {
  const form = document.getElementById("generate-form");
  if (!form) {
    console.error("Generate form not found");
    return;
  }

  const formData = new FormData(form);
  const curriculumId = formData.get("curriculum_id");

  console.log("generateSchedules called with curriculum:", curriculumId);

  clearValidationErrors();

  // Validation checks
  const validationErrors = [];

  if (!curriculumId) {
    validationErrors.push("Please select a curriculum");
    highlightField("curriculum_id", true);
  }

  if (window.sectionsData.length === 0) {
    validationErrors.push("No sections available for the current semester");
  }

  if (window.curriculumCourses.length === 0) {
    validationErrors.push("No courses available for the selected curriculum");
  }

  if (!window.faculty || window.faculty.length === 0) {
    validationErrors.push("No faculty members available for assignment");
  }

  if (!window.classrooms || window.classrooms.length === 0) {
    validationErrors.push("No classrooms available for assignment");
  }

  if (validationErrors.length > 0) {
    showValidationToast(validationErrors);
    return;
  }

  clearValidationErrors();

  // Show loading overlay
  const loadingOverlay = document.getElementById("loading-overlay");
  if (loadingOverlay) {
    loadingOverlay.classList.remove("hidden");
    console.log("⏳ Loading overlay shown");
  }

  const data = {
    action: "generate_schedule",
    curriculum_id: curriculumId,
    semester_id: formData.get("semester_id"),
    tab: "generate",
  };

  console.log("🚀 Sending data to backend:", data);
  const startTime = performance.now();

  fetch("/chair/generate-schedules", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams(data),
  })
    .then((response) => {
      console.log("📡 Response received, status:", response.status);
      if (!response.ok)
        throw new Error(`HTTP error! status: ${response.status}`);
      return response.text();
    })
    .then((text) => {
      console.log(
        "📄 Raw response received (first 500 chars):",
        text.substring(0, 500)
      );

      let responseData;
      try {
        responseData = JSON.parse(text);
      } catch (e) {
        console.error("❌ Invalid JSON response:", text);
        throw new Error("Invalid response format: " + e.message);
      }

      const fetchTime = performance.now() - startTime;
      console.log(`⏱️ Fetch completed in ${fetchTime.toFixed(2)}ms`);
      console.log("📊 Generation response:", responseData);

      if (responseData.success) {
        // Keep loading visible during updates
        console.log("🔄 Processing response data...");

        // Step 1: Update schedule data
        window.scheduleData = responseData.schedules || [];
        console.log(`✅ Updated ${window.scheduleData.length} schedules`);

        // Step 2: Update UI asynchronously with proper sequencing
        updateUIAfterGeneration(responseData, loadingOverlay, startTime);
        setTimeout(() => onSchedulesGenerated(), 500);
      } else {
        hideLoadingAndShowError(
          loadingOverlay,
          responseData.message || "Failed to generate schedules"
        );
      }
    })
    .catch((error) => {
      console.error("❌ Error:", error);
      hideLoadingAndShowError(
        loadingOverlay,
        "Error generating schedules: " + error.message
      );
    });
}

// ✅ ENHANCED TRANSFORMER - Splits ALL time slots from combined strings

// Parse all time slots from combined string
function parseAllTimeSlots(timeString, roomString) {
  console.log("🔍 Parsing time string:", timeString);
  console.log("🔍 Room string:", roomString);
  
  const slots = [];
  
  // Split by semicolon first (multiple time slots)
  const segments = timeString.split(';');
  
  segments.forEach(segment => {
    segment = segment.trim();
    if (!segment) return;
    
    console.log("  📝 Processing segment:", segment);
    
    // Pattern: "MWF 7:30-8:30 am Room 1" or "T 10:30 am-12:30 pm Laboratory 1"
    // Or just: "7:30-8:30 am Room 1" (days from parent)
    
    // Extract days from this segment (or use parent days)
    let days = null;
    let remainingSegment = segment;
    
    // Check if segment starts with day codes
    const dayCodeMatch = segment.match(/^([MTWFS]+|TTH|TH)\s+/);
    if (dayCodeMatch) {
      days = parseDays(dayCodeMatch[1]);
      remainingSegment = segment.substring(dayCodeMatch[0].length);
    }
    
    // Extract time: handles both "7:30-8:30 am" and "10:30 am-12:30 pm"
    const timeMatch = remainingSegment.match(/(\d{1,2}:\d{2})\s*(?:am|pm)?\s*-\s*(\d{1,2}:\d{2})\s*(am|pm)/i);
    
    if (!timeMatch) {
      console.warn("  ⚠️ No time match in:", remainingSegment);
      return;
    }
    
    let startTime = timeMatch[1];
    let endTime = timeMatch[2];
    const period = timeMatch[3].toLowerCase();
    
    // Check if start time has am/pm, if not, use end time's period
    const startHasPeriod = remainingSegment.match(new RegExp(startTime + '\\s*(am|pm)', 'i'));
    const startPeriod = startHasPeriod ? startHasPeriod[1].toLowerCase() : period;
    
    startTime = convertTo24Hour(startTime, startPeriod);
    endTime = convertTo24Hour(endTime, period);
    
    // Extract room from this segment
    let room = 'Online';
    const roomMatch = remainingSegment.match(/(Room|Laboratory|Lab)\s+\d+/i);
    if (roomMatch) {
      room = roomMatch[0];
    } else if (remainingSegment.toLowerCase().includes('online')) {
      room = 'Online';
    } else {
      // Try to extract from end of string
      const parts = remainingSegment.split(/\s+/);
      const lastPart = parts[parts.length - 1];
      if (lastPart && lastPart.length > 0) {
        // Reconstruct truncated room names
        if (roomString && roomString.startsWith('om ')) {
          room = 'Room ' + roomString.substring(3);
        } else if (roomString && roomString.startsWith('ry ')) {
          room = 'Laboratory ' + roomString.substring(3);
        } else if (roomString && roomString !== 'line') {
          room = roomString;
        }
      }
    }
    
    slots.push({
      days: days,
      startTime: startTime,
      endTime: endTime,
      room: room
    });
    
    console.log("  ✅ Extracted slot:", {
      days: days ? days.join(', ') : 'inherit',
      startTime,
      endTime,
      room
    });
  });
  
  console.log(`✅ Total slots extracted: ${slots.length}`);
  return slots;
}

// ✅ NEW: Get sections data with proper mapping
function getSectionsForSchedule(backendSchedule) {
  // Try multiple sources for section information
  let sections = [];
  
  // 1. Direct section field
  if (backendSchedule.section) {
    sections = Array.isArray(backendSchedule.section) 
      ? backendSchedule.section 
      : [backendSchedule.section];
  }
  
  // 2. Section name field
  if (!sections.length && backendSchedule.section_name) {
    sections = [backendSchedule.section_name];
  }
  
  // 3. Sections array
  if (!sections.length && backendSchedule.sections) {
    sections = Array.isArray(backendSchedule.sections) 
      ? backendSchedule.sections 
      : [backendSchedule.sections];
  }
  
  // 4. From sectionsData based on year level
  if (!sections.length && backendSchedule.year_level && window.sectionsData) {
    const yearLevel = backendSchedule.year_level;
    const matchingSections = window.sectionsData.filter(s => 
      s.year_level === yearLevel || 
      s.year_level?.toLowerCase() === yearLevel?.toLowerCase()
    );
    
    if (matchingSections.length > 0) {
      sections = matchingSections.map(s => s.section_name);
      console.log(`📚 Found ${sections.length} sections for ${yearLevel}:`, sections);
    }
  }
  
  // 5. Fallback: get all sections for current semester
  if (!sections.length && window.sectionsData && window.sectionsData.length > 0) {
    sections = window.sectionsData.slice(0, 3).map(s => s.section_name); // Limit to first 3
    console.warn(`⚠️ No section mapping found, using default sections:`, sections);
  }
  
  // 6. Ultimate fallback
  if (!sections.length) {
    sections = ['Section A'];
    console.warn(`⚠️ No sections available, using fallback: Section A`);
  }
  
  return sections;
}

// ✅ NEW: Get year level from course code or other sources
function getYearLevel(backendSchedule) {
  // Try direct year_level field
  if (backendSchedule.year_level) {
    return backendSchedule.year_level;
  }
  
  // Try to extract from course code (e.g., "CC 101" = 1st year)
  const courseCode = backendSchedule.course_code || '';
  const numberMatch = courseCode.match(/(\d)0\d/); // Matches 101, 201, 301, 401
  
  if (numberMatch) {
    const yearNum = parseInt(numberMatch[1]);
    if (yearNum >= 1 && yearNum <= 4) {
      const yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
      return yearLevels[yearNum - 1];
    }
  }
  
  // Fallback
  return '1st Year';
}

// Enhanced transformer that handles ALL time slots AND sections
function transformBackendSchedule(backendSchedule, baseIndex) {
  console.log("🔄 Transforming schedule:", backendSchedule);
  
  try {
    const timeRoomString = backendSchedule.time || '';
    const roomString = backendSchedule.room || '';
    const parentDays = backendSchedule.days || '';
    
    if (!timeRoomString) {
      console.warn("⚠️ No time string in schedule");
      return null;
    }
    
    // Parse ALL time slots from the combined string
    const timeSlots = parseAllTimeSlots(timeRoomString, roomString);
    
    if (timeSlots.length === 0) {
      console.warn("⚠️ No time slots extracted from:", timeRoomString);
      return null;
    }
    
    console.log(`📊 Found ${timeSlots.length} time slots in this schedule`);
    
    // Get parent days if slots don't have their own
    const parentDaysList = parseDays(parentDays);
    
    // ✅ Get sections for this schedule
    const sections = getSectionsForSchedule(backendSchedule);
    console.log(`📚 Using ${sections.length} section(s):`, sections);
    
    // ✅ Get year level
    const yearLevel = getYearLevel(backendSchedule);
    console.log(`📖 Year level: ${yearLevel}`);
    
    // Create schedules for each time slot AND each section
    const transformedSchedules = [];
    let scheduleIdCounter = 0;
    
    // Loop through sections
    sections.forEach(sectionName => {
      // Loop through time slots
      timeSlots.forEach((slot, slotIndex) => {
        // Use slot's days or fall back to parent days
        const daysToUse = slot.days || parentDaysList;
        
        // Create a schedule entry for each day
        daysToUse.forEach(day => {
          transformedSchedules.push({
            schedule_id: (baseIndex * 1000) + scheduleIdCounter++,
            course_code: backendSchedule.course_code || 'Unknown',
            course_name: backendSchedule.course_name || backendSchedule.course_code || 'Unknown',
            faculty_name: backendSchedule.instructor || backendSchedule.faculty_name || 'TBA',
            day_of_week: day,
            start_time: slot.startTime + ':00',
            end_time: slot.endTime + ':00',
            room_name: slot.room,
            section_name: sectionName,
            section_id: null, // Will be populated if needed
            year_level: yearLevel,
            semester_id: window.currentSemester?.semester_id || null,
            department_id: window.departmentId || null,
            college_id: window.jsData?.collegeId || null,
            lecture_units: backendSchedule.lecture_units || 0,
            lab_units: backendSchedule.lab_units || 0,
            current_students: 0,
            max_students: 40
          });
        });
      });
    });
    
    console.log(`✅ Created ${transformedSchedules.length} individual schedule entries (${sections.length} sections × ${timeSlots.length} time slots × days)`);
    return transformedSchedules;
    
  } catch (error) {
    console.error("❌ Error transforming schedule:", error, backendSchedule);
    return null;
  }
}

// Convert 12-hour time to 24-hour format
function convertTo24Hour(timeStr, period) {
  let [hours, minutes] = timeStr.split(':').map(Number);
  
  if (period === 'pm' && hours !== 12) {
    hours += 12;
  } else if (period === 'am' && hours === 12) {
    hours = 0;
  }
  
  return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
}

// Parse day codes with better handling of combined codes
function parseDays(dayCode) {
  if (!dayCode) return ['Monday'];
  
  dayCode = dayCode.toUpperCase().trim();
  
  const dayMap = {
    'M': 'Monday',
    'T': 'Tuesday',
    'W': 'Wednesday',
    'TH': 'Thursday',
    'F': 'Friday',
    'S': 'Saturday',
    'SU': 'Sunday'
  };
  
  // Handle special cases first
  if (dayCode === 'MWF') return ['Monday', 'Wednesday', 'Friday'];
  if (dayCode === 'TTH' || dayCode === 'TH') return ['Tuesday', 'Thursday'];
  if (dayCode === 'MW') return ['Monday', 'Wednesday'];
  if (dayCode === 'MTWTHF') return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
  
  const days = [];
  let i = 0;
  
  while (i < dayCode.length) {
    // Try two-character match first
    if (i < dayCode.length - 1) {
      const twoChar = dayCode.substring(i, i + 2);
      if (dayMap[twoChar]) {
        days.push(dayMap[twoChar]);
        i += 2;
        continue;
      }
    }
    
    // Try single character
    const oneChar = dayCode[i];
    if (dayMap[oneChar]) {
      days.push(dayMap[oneChar]);
    }
    i++;
  }
  
  // Remove duplicates while preserving order
  const uniqueDays = [...new Set(days)];
  
  return uniqueDays.length > 0 ? uniqueDays : ['Monday'];
}

// ✅ UPDATED: updateUIAfterGeneration with better logging
async function updateUIAfterGeneration(responseData, loadingOverlay, startTime) {
  try {
    console.log("🎨 ===== STARTING ENHANCED UI UPDATE =====");
    console.log("📦 Response schedules count:", responseData.schedules?.length || 0);
    console.log("📚 Available sections:", window.sectionsData?.length || 0);

    await new Promise(resolve => requestAnimationFrame(resolve));

    if (!responseData.schedules || !Array.isArray(responseData.schedules)) {
      console.error("❌ Invalid schedules in response!");
      throw new Error("No valid schedules returned from server");
    }

    if (responseData.schedules.length === 0) {
      console.warn("⚠️ Server returned 0 schedules!");
      showValidationToast(["No schedules were generated."]);
      if (loadingOverlay) loadingOverlay.classList.add("hidden");
      return;
    }

    console.log("🔄 Transforming schedules with enhanced parser...");
    console.log("📋 Sample backend schedule:", JSON.stringify(responseData.schedules[0], null, 2));
    
    // Transform all schedules
    const transformedSchedules = [];
    responseData.schedules.forEach((backendSchedule, index) => {
      const transformed = transformBackendSchedule(backendSchedule, index);
      if (transformed && Array.isArray(transformed)) {
        transformedSchedules.push(...transformed);
        console.log(`  ✅ Schedule ${index + 1} (${backendSchedule.course_code}): ${transformed.length} entries created`);
      }
    });

    console.log(`✅ TRANSFORMATION COMPLETE:`);
    console.log(`   Backend schedules: ${responseData.schedules.length}`);
    console.log(`   Frontend schedules: ${transformedSchedules.length}`);
    console.log(`   Expansion ratio: ${(transformedSchedules.length / responseData.schedules.length).toFixed(2)}x`);
    console.log(`   Unique sections: ${new Set(transformedSchedules.map(s => s.section_name)).size}`);
    console.log(`   Unique days: ${new Set(transformedSchedules.map(s => s.day_of_week)).size}`);

    if (transformedSchedules.length === 0) {
      console.error("❌ NO SCHEDULES AFTER TRANSFORMATION!");
      throw new Error("Schedule transformation failed - no valid schedules created");
    }

    // Show sample transformed schedule
    console.log("📋 Sample transformed schedule:", JSON.stringify(transformedSchedules[0], null, 2));

    // Store transformed schedules
    window.scheduleData = transformedSchedules;
    console.log(`✅ STORED ${window.scheduleData.length} SCHEDULES IN window.scheduleData`);

    // Update generation results
    const generationResults = document.getElementById("generation-results");
    if (generationResults) {
      generationResults.classList.remove("hidden");
      const totalCoursesEl = document.getElementById("total-courses");
      const totalSectionsEl = document.getElementById("total-sections");
      const successRateEl = document.getElementById("success-rate");
      if (totalCoursesEl) totalCoursesEl.textContent = responseData.totalCourses || responseData.schedules.length;
      if (totalSectionsEl) totalSectionsEl.textContent = new Set(transformedSchedules.map(s => s.section_name)).size;
      if (successRateEl) successRateEl.textContent = responseData.successRate || "100%";
    }

    // Update completion status
    updateScheduleCompletionStatus(responseData);

    // Force grid update with multiple attempts
    console.log("🔄 Forcing grid update...");
    
    for (let attempt = 1; attempt <= 3; attempt++) {
      console.log(`🔄 Grid update attempt ${attempt}/${3}...`);
      
      if (typeof safeUpdateScheduleDisplay === 'function') {
        safeUpdateScheduleDisplay(window.scheduleData);
      }
      
      if (typeof updateManualGrid === 'function') {
        updateManualGrid(window.scheduleData);
      }
      
      if (typeof updateViewGrid === 'function') {
        updateViewGrid(window.scheduleData);
      }
      
      if (attempt < 3) {
        await new Promise(resolve => setTimeout(resolve, 150));
      }
    }

    // Verify grids were populated
    await new Promise(resolve => setTimeout(resolve, 300));
    
    const manualGrid = document.getElementById("schedule-grid");
    if (manualGrid) {
      const scheduleCards = manualGrid.querySelectorAll('.schedule-card');
      console.log(`📊 Manual grid verification: ${scheduleCards.length} schedule cards found`);
      
      if (scheduleCards.length === 0) {
        console.warn("⚠️ Manual grid is empty, attempting emergency rebuild...");
        if (typeof emergencyGridRebuild === 'function') {
          emergencyGridRebuild(window.scheduleData);
        }
      } else {
        console.log(`✅ Grid successfully populated with ${scheduleCards.length} cards`);
      }
    }

    // Reinitialize drag and drop
    if (typeof initializeDragAndDrop === 'function') {
      initializeDragAndDrop();
      console.log("✅ Drag and drop initialized");
    }

    // Hide loading
    const totalTime = performance.now() - startTime;
    console.log(`✨ All updates completed in ${totalTime.toFixed(2)}ms`);
    
    if (loadingOverlay) {
      loadingOverlay.classList.add("hidden");
    }

    // Show notification
    const uniqueSections = new Set(transformedSchedules.map(s => s.section_name)).size;
    await new Promise(resolve => setTimeout(resolve, 100));
    showCompletionToast("success", "Schedules generated successfully!", [
      `${transformedSchedules.length} schedule entries created`,
      `${responseData.schedules.length} courses across ${uniqueSections} sections`,
      `Displaying on ${new Set(transformedSchedules.map(s => s.day_of_week)).size} days`,
    ]);

    // Auto-switch to manual tab
    console.log("🔀 Switching to manual tab in 800ms...");
    setTimeout(async () => {
      if (typeof switchTab === 'function') {
        switchTab('manual');
        console.log("✅ Switched to manual tab");
        
        // Post-switch refresh
        await new Promise(resolve => setTimeout(resolve, 300));
        
        console.log("🔄 Post-switch refresh...");
        if (typeof safeUpdateScheduleDisplay === 'function') {
          safeUpdateScheduleDisplay(window.scheduleData);
        }
        if (typeof updateManualGrid === 'function') {
          updateManualGrid(window.scheduleData);
        }
        if (typeof initializeDragAndDrop === 'function') {
          initializeDragAndDrop();
        }
        
        // Final verification
        await new Promise(resolve => setTimeout(resolve, 200));
        const finalGrid = document.getElementById("schedule-grid");
        if (finalGrid) {
          const finalCards = finalGrid.querySelectorAll('.schedule-card');
          console.log(`📊 FINAL VERIFICATION: ${finalCards.length} schedule cards visible`);
          
          if (finalCards.length === 0) {
            console.error("❌ Schedules still not visible after all attempts!");
            console.log("🚨 Triggering emergency rebuild...");
            if (typeof emergencyGridRebuild === 'function') {
              emergencyGridRebuild(window.scheduleData);
            }
          } else {
            console.log("✅✅✅ SUCCESS! All schedules are now visible!");
          }
        }
        
        console.log("🎉 ===== UPDATE PROCESS COMPLETE =====");
      }
    }, 800);

  } catch (error) {
    console.error("❌❌❌ CRITICAL ERROR during UI update:", error);
    console.error("Stack trace:", error.stack);
    console.error("Response data:", responseData);
    hideLoadingAndShowError(loadingOverlay, "Error updating display: " + error.message);
  }
}

console.log("✅ Enhanced schedule transformer loaded - handles sections and multiple time slots");

// Step 11: Auto-switch to manual tab to show the schedules
console.log("🔀 Auto-switching to manual tab...");
setTimeout(() => {
  if (typeof switchTab === 'function') {
    switchTab('manual');
    console.log("✅ Switched to manual tab");
    
    // Force one more refresh after tab switch
    setTimeout(() => {
      safeUpdateScheduleDisplay(window.scheduleData);
      if (typeof initializeDragAndDrop === 'function') {
        initializeDragAndDrop();
      }
      console.log("✅ Post-switch refresh complete");
    }, 300);
  } else {
    console.warn("⚠️ switchTab function not available");
  }
}, 800);

// Helper function to hide loading and show error
function hideLoadingAndShowError(loadingOverlay, message) {
  if (loadingOverlay) {
    loadingOverlay.classList.add("hidden");
  }
  showValidationToast([message]);
}

// Initialize event listeners
document.addEventListener("DOMContentLoaded", () => {
  console.log("DOM fully loaded, initializing generate_schedules.js");

  initializeScheduleData();

  const curriculumSelect = document.getElementById("curriculum_id");
  if (curriculumSelect) {
    curriculumSelect.addEventListener("change", updateCourses);
  } else {
    console.error("Curriculum select element not found");
  }

  const generateButton = document.getElementById("generate-btn");
  if (generateButton) {
    // Remove any existing event listeners by cloning the button
    const newButton = generateButton.cloneNode(true);
    generateButton.parentNode.replaceChild(newButton, generateButton);

    // Add our properly async event listener
    newButton.addEventListener("click", generateSchedules);
    console.log("✅ Generate button event listener attached");
  } else {
    console.error("Generate button not found");
  }
});
