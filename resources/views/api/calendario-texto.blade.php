<style>
  .cda-events-section { margin: 0; padding: 0; }
  .cda-events-section .section-eyebrow { display: flex; align-items: center; gap: 10px; font-size: 12px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; color: #E85D24; margin-bottom: 8px; font-family: 'Lato', sans-serif; }
  .cda-events-section .section-eyebrow::after { content: ''; flex: 1; height: 1px; background: #E85D24; opacity: 0.3; }
  .cda-events-section .section-eyebrow svg { width: 16px; height: 16px; fill: none; stroke: #E85D24; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .cda-events-section .events-section-title { font-family: 'Bebas Neue', sans-serif; font-size: 48px; letter-spacing: 3px; color: #1a1a1a; margin-bottom: 40px; line-height: 1; }
  .cda-events-section .months-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 32px; }
  @media (max-width: 768px) {
    .cda-events-section .months-grid { grid-template-columns: 1fr; }
    .cda-events-section .events-section-title { font-size: 36px; }
  }
  .cda-events-section .month-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
  .cda-events-section .month-name { font-family: 'Bebas Neue', sans-serif; font-size: 30px; letter-spacing: 3px; color: #E85D24; line-height: 1; }
  .cda-events-section .month-line { flex: 1; height: 2px; background: linear-gradient(to right, #E85D24, transparent); }
  .cda-events-section .event-item { display: flex; align-items: flex-start; gap: 14px; padding: 12px 10px; border-radius: 10px; transition: background 0.15s; cursor: default; }
  .cda-events-section .event-item:hover { background: rgba(232, 93, 36, 0.06); }
  .cda-events-section .event-item + .event-item { border-top: 1px solid rgba(0,0,0,0.07); }
  .cda-events-section .event-date-badge { flex-shrink: 0; min-width: 80px; background: #E85D24; color: #fff; font-family: 'Bebas Neue', sans-serif; font-size: 17px; letter-spacing: 1px; text-align: center; padding: 6px 8px; border-radius: 8px; line-height: 1.2; }
  .cda-events-section .event-name { font-size: 13px; font-weight: 700; color: #1a1a1a; letter-spacing: 0.4px; text-transform: uppercase; line-height: 1.3; margin-bottom: 3px; font-family: 'Lato', sans-serif; }
  .cda-events-section .event-desc { font-size: 12px; color: #777; line-height: 1.4; font-weight: 300; font-family: 'Lato', sans-serif; }
  .cda-events-section .events-empty { font-family: 'Lato', sans-serif; color: #777; font-size: 14px; }
</style>
<section class="cda-events-section events-section">
  <div class="section-eyebrow">
    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    Agenda
  </div>
  <h4 class="events-section-title">Próximos Eventos</h4>
  @if ($mesesConEventos === [])
  <p class="events-empty">No hay eventos próximos en el calendario.</p>
  @else
  <div class="months-grid">
    @foreach ($mesesConEventos as $mes)
    <div class="month-col">
      <div class="month-header">
        <span class="month-name">{{ $mes['etiqueta'] }}</span>
        <div class="month-line"></div>
      </div>
      @foreach ($mes['eventos'] as $evento)
      <div class="event-item">
        <div class="event-date-badge">{{ $evento['badge'] ?? '' }}</div>
        <div class="event-info">
          <div class="event-name">{{ $evento['titulo'] }}</div>
          @if (trim((string) ($evento['descripcion'] ?? '')) !== '')
          <div class="event-desc">{{ $evento['descripcion'] }}</div>
          @endif
        </div>
      </div>
      @endforeach
    </div>
    @endforeach
  </div>
  @endif
</section>
