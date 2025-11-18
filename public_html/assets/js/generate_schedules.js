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

async function updateUIAfterGeneration(
  responseData,
  loadingOverlay,
  startTime
) {
  try {
    console.log("🎨 ===== STARTING UI UPDATE =====");
    console.log("📦 Response schedules:", responseData.schedules?.length || 0);

    // Validate response
    if (!responseData.schedules || !Array.isArray(responseData.schedules)) {
      throw new Error("Invalid schedules in response");
    }

    if (responseData.schedules.length === 0) {
      console.warn("⚠️ No schedules generated");
      if (loadingOverlay) loadingOverlay.classList.add("hidden");
      showValidationToast(["No schedules were generated."]);
      return;
    }

    // ✅ Transform backend schedules
    console.log("🔄 Transforming schedules...");
    const transformedSchedules = [];

    responseData.schedules.forEach((backendSchedule, index) => {
      const transformed = transformBackendSchedule(backendSchedule, index);
      if (transformed && Array.isArray(transformed)) {
        transformedSchedules.push(...transformed);
      }
    });

    console.log(
      `✅ Transformed: ${transformedSchedules.length} schedule entries`
    );

    if (transformedSchedules.length === 0) {
      throw new Error("Schedule transformation failed");
    }

    // ✅ CRITICAL: Store globally IMMEDIATELY
    window.scheduleData = transformedSchedules;
    console.log(`✅ Stored ${window.scheduleData.length} schedules globally`);

    // Hide loading
    if (loadingOverlay) {
      loadingOverlay.classList.add("hidden");
    }

    const totalTime = performance.now() - startTime;
    console.log(`✅ UI updated in ${totalTime.toFixed(2)}ms`);

    // Show success notification
    const uniqueSections = new Set(
      transformedSchedules.map((s) => s.section_name)
    ).size;

    showCompletionToast("success", "Schedules Generated!", [
      `${transformedSchedules.length} schedule entries created`,
      `${responseData.schedules.length} courses across ${uniqueSections} sections`,
      responseData.unassignedCourses?.length > 0
        ? `⚠️ ${responseData.unassignedCourses.length} courses need manual scheduling`
        : "All courses scheduled successfully!",
      "Page will refresh in 2 seconds...",
    ]);

    // ✅ AUTO-REFRESH: Redirect to manual tab with page reload
    console.log("🔄 Preparing to refresh page...");

    // Wait 2 seconds to let user see the success message
    setTimeout(() => {
      console.log("🔄 Refreshing page and switching to manual tab...");

      // Set the tab parameter in URL
      const url = new URL(window.location);
      url.searchParams.set("tab", "manual");

      // Reload the page with the new URL
      window.location.href = url.toString();
    }, 2000);
  } catch (error) {
    console.error("❌ Error in updateUIAfterGeneration:", error);
    hideLoadingAndShowError(
      loadingOverlay,
      "Error displaying schedules: " + error.message
    );
  }
}

function updateGenerationResults(responseData, transformedSchedules) {
  const generationResults = document.getElementById("generation-results");
  if (!generationResults) return;

  generationResults.classList.remove("hidden");

  const totalCoursesEl = document.getElementById("total-courses");
  const totalSectionsEl = document.getElementById("total-sections");
  const successRateEl = document.getElementById("success-rate");

  if (totalCoursesEl) {
    totalCoursesEl.textContent =
      responseData.totalCourses || responseData.schedules.length;
  }

  if (totalSectionsEl) {
    const uniqueSections = new Set(
      transformedSchedules.map((s) => s.section_name)
    ).size;
    totalSectionsEl.textContent = uniqueSections;
  }

  if (successRateEl) {
    successRateEl.textContent = responseData.successRate || "100%";
  }
}

// ===== FIX 8: Enhanced Tab Switch Handler =====
window.addEventListener("DOMContentLoaded", function () {
  console.log("🚀 generate_schedules.js loaded");

  // Override switchTab to force grid refresh
  const originalSwitchTab = window.switchTab;

  window.switchTab = function (tabName) {
    console.log("🔀 Enhanced switchTab called:", tabName);

    // Call original
    if (typeof originalSwitchTab === "function") {
      originalSwitchTab(tabName);
    }

    // Force grid refresh for manual and schedule tabs
    if (
      (tabName === "manual" || tabName === "schedule") &&
      window.scheduleData &&
      window.scheduleData.length > 0
    ) {
      console.log("🔄 Forcing grid refresh for", tabName, "tab");

      setTimeout(() => {
        if (typeof window.safeUpdateScheduleDisplay === "function") {
          window.safeUpdateScheduleDisplay(window.scheduleData);

          // Reinitialize drag and drop for manual tab
          if (
            tabName === "manual" &&
            typeof initializeDragAndDrop === "function"
          ) {
            setTimeout(() => {
              initializeDragAndDrop();
              console.log("✅ Drag and drop reinitialized");
            }, 100);
          }
        }
      }, 200);
    }
  };

  console.log("✅ Enhanced switchTab installed");
});

function showIncompleteScheduleBanner(responseData) {
  console.log("⚠️ Showing incomplete schedule banner");

  // Remove existing banner
  const existingBanner = document.getElementById("incomplete-schedule-banner");
  if (existingBanner) {
    existingBanner.remove();
  }

  if (
    !responseData.unassignedCourses ||
    responseData.unassignedCourses.length === 0
  ) {
    return;
  }

  // Find insertion point (after nav tabs, before tab content)
  const navTabs = document.querySelector("nav.flex.space-x-1");
  if (!navTabs) {
    console.warn("⚠️ Nav tabs not found for banner insertion");
    return;
  }

  const banner = document.createElement("div");
  banner.id = "incomplete-schedule-banner";
  banner.className =
    "mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded-lg shadow-sm";

  banner.innerHTML = `
    <div class="flex items-start">
      <div class="flex-shrink-0">
        <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
      </div>
      <div class="ml-3 flex-1">
        <h3 class="text-sm font-semibold text-yellow-800">⚠️ Incomplete Schedule Generation</h3>
        <div class="mt-2 text-sm text-yellow-700">
          <p class="mb-2"><strong>${
            responseData.unassignedCourses.length
          }</strong> course(s) could not be scheduled automatically:</p>
          <ul class="list-disc list-inside ml-2 max-h-32 overflow-y-auto">
            ${responseData.unassignedCourses
              .map(
                (c) => `
              <li class="py-1">
                <strong>${escapeHtml(c.course_code)}</strong> - ${escapeHtml(
                  c.course_name || ""
                )}
                ${
                  c.curriculum_year
                    ? `(Year: ${escapeHtml(c.curriculum_year)})`
                    : ""
                }
              </li>
            `
              )
              .join("")}
          </ul>
          <div class="mt-3 p-2 bg-yellow-100 rounded">
            <p class="text-xs">
              <strong>Success Rate:</strong> ${
                responseData.successRate || "0%"
              } 
              (${
                (responseData.totalCourses || 0) -
                responseData.unassignedCourses.length
              } of ${responseData.totalCourses || 0} courses scheduled)
            </p>
          </div>
        </div>
        <div class="mt-3 flex space-x-2">
          <button onclick="switchTab('manual')" class="text-sm font-medium px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded transition-colors">
            <i class="fas fa-edit mr-1"></i>Go to Manual Edit
          </button>
          <button onclick="tryRegenerateIncomplete()" class="text-sm font-medium px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors">
            <i class="fas fa-sync-alt mr-1"></i>Try Regenerate
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

  // Insert after nav tabs
  if (navTabs.parentElement) {
    navTabs.parentElement.insertBefore(banner, navTabs.nextSibling);
    console.log("✅ Incomplete schedule banner displayed");
  }
}

window.tryRegenerateIncomplete = function () {
  console.log("🔄 Attempting to regenerate incomplete schedules...");

  const form = document.getElementById("generate-form");
  if (!form) {
    showValidationToast(["Generate form not found"]);
    return;
  }

  const formData = new FormData(form);
  const curriculumId = formData.get("curriculum_id");

  if (!curriculumId) {
    showValidationToast(["Please select a curriculum first"]);
    return;
  }

  // Show loading
  const loadingOverlay = document.getElementById("loading-overlay");
  if (loadingOverlay) {
    loadingOverlay.classList.remove("hidden");
  }

  // Call generate with force flag
  const data = {
    action: "generate_schedule",
    curriculum_id: curriculumId,
    semester_id: formData.get("semester_id"),
    force_regenerate: "true",
  };

  fetch("/chair/generate-schedules", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(data),
  })
    .then((response) => response.json())
    .then((result) => {
      if (loadingOverlay) loadingOverlay.classList.add("hidden");

      if (result.success) {
        // Transform and update
        const transformedSchedules = [];
        result.schedules.forEach((s, i) => {
          const transformed = transformBackendSchedule(s, i);
          if (transformed && Array.isArray(transformed)) {
            transformedSchedules.push(...transformed);
          }
        });

        window.scheduleData = transformedSchedules;
        window.safeUpdateScheduleDisplay(window.scheduleData);

        if (result.unassignedCourses && result.unassignedCourses.length > 0) {
          showIncompleteScheduleBanner(result);
          showCompletionToast("warning", "Partial Success", [
            `${result.unassignedCourses.length} courses still need manual scheduling`,
          ]);
        } else {
          const banner = document.getElementById("incomplete-schedule-banner");
          if (banner) banner.remove();
          showCompletionToast("success", "All Courses Scheduled!", [
            "Successfully scheduled all remaining courses",
          ]);
        }
      } else {
        showValidationToast([result.message || "Regeneration failed"]);
      }
    })
    .catch((error) => {
      if (loadingOverlay) loadingOverlay.classList.add("hidden");
      console.error("Regeneration error:", error);
      showValidationToast(["Error during regeneration: " + error.message]);
    });
};


window.emergencyGridRebuild = function (schedules) {
  console.log("🚨 EMERGENCY GRID REBUILD");

  if (!schedules || schedules.length === 0) {
    console.error("❌ No schedules to rebuild");
    return;
  }

  const manualGrid = document.getElementById("schedule-grid");
  if (!manualGrid) {
    console.error("❌ Manual grid not found");
    return;
  }

  // Clear grid
  manualGrid.innerHTML =
    '<div class="col-span-8 text-center py-4 text-gray-500">Building schedule grid...</div>';

  // Force immediate rebuild
  setTimeout(() => {
    if (typeof updateManualGrid === "function") {
      updateManualGrid(schedules);
      console.log("✅ Emergency rebuild complete");

      // Reinitialize drag and drop
      setTimeout(() => {
        if (typeof initializeDragAndDrop === "function") {
          initializeDragAndDrop();
          console.log("✅ Drag and drop reinitialized");
        }
      }, 100);
    }
  }, 100);
};

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

// Make functions globally available
window.updateUIAfterGeneration = updateUIAfterGeneration;
window.safeUpdateScheduleDisplay = safeUpdateScheduleDisplay;
window.emergencyGridRebuild = emergencyGridRebuild;

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
