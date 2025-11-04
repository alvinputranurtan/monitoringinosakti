$(document).ready(function () {
  let dashboardInterval; // Variable to store dashboard interval

  function loadPage(page, title, push = true) {
    $("#main-content").load("pages/" + page, function () {
      // Clear any existing intervals when loading new page
      if (dashboardInterval) {
        clearInterval(dashboardInterval);
        dashboardInterval = null;
      }

      // Initialize specific page functionality
      if (page === "dashboard.php") {
        // Start dashboard updates only when on dashboard page
        dashboardInterval = setInterval(updateDashboard, 5000);
      } else if (page === "grafik.php") {
        // Ganti loadGrafik() dengan ChartManager.initialize()
        ChartManager.initialize();
      }
    });

    $(".toggle-title").text(title);
    $(".menu-link").removeClass("active");
    $('.menu-link[data-page="' + page + '"]').addClass("active");

    if (push) {
      history.pushState({ page: page, title: title }, "", "?page=" + page.replace(".php", ""));
    }

    if (window.innerWidth <= 768) {
      $("#sidebar").removeClass("active");
      $("#overlay").removeClass("active");
      $(".toggle-sidebar").removeClass("fa-times").addClass("fa-bars");
    }
  }

  $(".toggle-sidebar").click(function () {
    $("#sidebar").toggleClass("active");
    $("#overlay").toggleClass("active");
    $(".toggle-sidebar").toggleClass("fa-bars fa-times");
  });

  $(".close-sidebar, #overlay").click(function () {
    $("#sidebar").removeClass("active");
    $("#overlay").removeClass("active");
    $(".toggle-sidebar").removeClass("fa-times").addClass("fa-bars");
  });

  $(".menu-link").click(function (e) {
    e.preventDefault();
    const page = $(this).data("page");
    const title = $(this).data("title");
    loadPage(page, title);
  });

  // Handle back/forward browser
  window.onpopstate = function () {
    const params = new URLSearchParams(location.search);
    const page = (params.get("page") || "dashboard") + ".php";
    const title = $('.menu-link[data-page="' + page + '"]').data("title") || "Dashboard";
    loadPage(page, title, false);
  };

    // Cek kalau page awal adalah grafik → panggil fungsi manual
  const initialPage = new URLSearchParams(window.location.search).get("page") || "dashboard";
  if (initialPage === "grafik") {
    ChartManager.initialize();
  }



// jam-script.js
function updateCheckboxStates() {
  $(".jam-item").each(function () {
    const mulai = $(this).find('input[name="mulai[]"]').val();
    const menit = $(this).find('input[name="menit[]"]').val();
    const checkbox = $(this).find('input[type="checkbox"]');

    let isValid = false;

    if (mulai && menit) {
      // Validasi: menit harus antara 1-60
      const menitValue = parseInt(menit);
      if (menitValue > 0 && menitValue <= 60) {
        isValid = true;
      }
    }

    if (isValid) {
      checkbox.prop("disabled", false);
    } else {
      checkbox.prop("disabled", true).prop("checked", false);
    }
  });
}

function hapusListKosongKecualiSatu() {
  const kosongItems = $(".jam-item").filter(function () {
    const mulai = $(this).find('input[name="mulai[]"]').val();
    const menit = $(this).find('input[name="menit[]"]').val();
    const checked = $(this).find('input[type="checkbox"]').is(':checked');
    return !mulai && !menit && !checked;
  });

  if (kosongItems.length > 1) {
    kosongItems.slice(1).remove();
  }
}

function tambahListJikaChecklistDicek() {
  const checkedItems = $(".jam-item").filter(function () {
    return $(this).find('input[type="checkbox"]').is(':checked');
  });

  const kosongItems = $(".jam-item").filter(function () {
    const mulai = $(this).find('input[name="mulai[]"]').val();
    const menit = $(this).find('input[name="menit[]"]').val();
    const checked = $(this).find('input[type="checkbox"]').is(':checked');
    return !mulai && !menit && !checked;
  });

  if (checkedItems.length > 0 && kosongItems.length === 0) {
    const newItem = `
      <div class="d-flex align-items-center mb-3 jam-item">
        <div class="me-2 w-100">
          <label class="form-label small mb-1">Mulai</label>
          <input type="time" class="form-control form-control-custom" name="mulai[]">
        </div>

        <div class="me-2 w-100">
          <label class="form-label small mb-1">Menit</label>
          <input type="number" class="form-control form-control-custom" name="menit[]" min="1" max="60">
        </div>

        <div class="ms-3">
          <input type="checkbox" name="aktif[]" class="form-check-input mt-4" disabled>
        </div>
      </div>
    `;
    $("#jamList").append(newItem);
  }
}

function handleFormLogic() {
  updateCheckboxStates();
  tambahListJikaChecklistDicek();
  hapusListKosongKecualiSatu();
}

$(document).ready(function () {
  handleFormLogic();
});

$(document).on("input change", 'input[name="mulai[]"], input[name="menit[]"], input[type="checkbox"]', function () {
  handleFormLogic();
});

// Update dashboard function
function updateDashboard() {
    $.ajax({
        url: 'pages/ajax_dashboard.php',
        success: function(response) {
            // Update dashboard values
            // ...existing dashboard update code...
        }
    });
}

});
