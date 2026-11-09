<script>
(function(){
  var deptEl = document.getElementById('<?= e($deptFieldId) ?>');
  var dateEl = document.getElementById('<?= e($dateFieldId) ?>');
  var timeEl = document.getElementById('<?= e($timeFieldId) ?>');
  var box = document.getElementById('<?= e($boxId) ?>');
  if (!dateEl || !timeEl || !box) { return; }
  var slotsUrl = <?= json_encode(url('slots.php')) ?>;

  function render(slots) {
    box.innerHTML = '';
    if (!slots.length) { box.innerHTML = '<p class="hint">Select a date and the department you want to visit to view the available time slots.</p>'; return; }
    var grid = document.createElement('div');
    grid.style.display = 'grid';
    grid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(72px, 1fr))';
    grid.style.gap = '6px';
    slots.forEach(function(s){
      var b = document.createElement('button');
      b.type = 'button';
      b.textContent = s.time;
      b.className = 'btn small ' + (s.free ? 'ghost' : '') ;
      b.disabled = !s.free;
      if (!s.free) { b.style.opacity = '.35'; b.style.textDecoration = 'line-through'; b.title = 'Iyi saha yarafashwe'; }
      if (timeEl.value === s.time) { b.classList.add('active'); b.style.background = 'var(--teal)'; b.style.color = '#fff'; }
      b.addEventListener('click', function(){
        timeEl.value = s.time;
        Array.prototype.forEach.call(grid.children, function(c){ c.style.background=''; c.style.color=''; });
        b.style.background = 'var(--teal)'; b.style.color = '#fff';
      });
      grid.appendChild(b);
    });
    box.appendChild(grid);
  }

  function load() {
    var deptVal = deptEl ? deptEl.value : <?= json_encode($fixedDepartmentId ?? '') ?>;
    var dateVal = dateEl.value;
    if (!deptVal || !dateVal) { box.innerHTML = '<p class="hint">Select a date and the department you want to visit to view the available time slots..</p>'; return; }
    box.innerHTML = '<p class="hint">Turimo kureba amasaha abonetse…</p>';
    fetch(slotsUrl + '?department_id=' + encodeURIComponent(deptVal) + '&date=' + encodeURIComponent(dateVal))
      .then(function(r){ return r.json(); })
      .then(function(data){ render(data.slots || []); })
      .catch(function(){ box.innerHTML = '<p class="hint">Ntibyashobotse kubona amasaha ubu. Andika isaha intoki hepfo.</p>'; });
  }

  if (deptEl) { deptEl.addEventListener('change', load); }
  dateEl.addEventListener('change', load);
  if (dateEl.value) { load(); }
})();
</script>
