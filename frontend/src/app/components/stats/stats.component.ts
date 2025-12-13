import { Component, Input, OnInit, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { ActivatedRoute, Router } from '@angular/router';
import { ContentLoaderComponent } from '../content-loader/content-loader.component';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-stats',
  standalone: true,
  imports: [CommonModule, ContentLoaderComponent],
  templateUrl: './stats.component.html',
  styleUrls: ['./stats.component.scss']
})
export class StatsComponent implements OnInit, OnChanges {
  @Input() planId: number | null = null;
  loading = true;
  error = '';

  plan: any = null;
  stats: any = null;
  dailyData: any[] = [];

  // Chart dimensions
  chartWidth = 800;
  chartHeight = 400;
  chartPadding = { top: 40, right: 40, bottom: 60, left: 60 };

  constructor(
    private http: HttpClient,
    private route: ActivatedRoute,
    private router: Router
  ) { }

  ngOnInit() {
    this.route.queryParams.subscribe(params => {
      // If plan_id param exists, use it. Otherwise rely on Input() or default to null (Global)
      const id = params['plan_id'] ? +params['plan_id'] : this.planId;
      this.planId = id; // Could be null
      this.loadStats();
    });
  }

  ngOnChanges(changes: SimpleChanges) {
    if (changes['planId']) {
      this.loadStats();
    }
  }

  loadStats() {
    this.loading = true;
    this.error = '';

    let url = '';
    if (this.planId) {
      url = `${environment.apiUrl}/get_stats.php?plan_id=${this.planId}`;
    } else {
      const userId = localStorage.getItem('user_id') || '1';
      url = `${environment.apiUrl}/get_global_stats.php?user_id=${userId}`;
    }

    this.http.get(url)
      .subscribe({
        next: (res: any) => {
          if (res.success) {
            this.plan = res.plan;
            this.stats = res.stats;
            this.dailyData = (res.daily_data || []).sort((a: any, b: any) =>
              new Date(a.date).getTime() - new Date(b.date).getTime()
            );
            this.loading = false;
          } else {
            this.error = res.message || 'Failed to load stats';
            this.loading = false;
          }
        },
        error: (err) => {
          console.error('Stats load error:', err);
          this.error = err.status === 0 ? 'Network error. Please check your connection.' : 'Error loading stats. Please try again.';
          this.loading = false;
        }
      });
  }

  // Chart calculations
  getChartInnerWidth(): number {
    return this.chartWidth - this.chartPadding.left - this.chartPadding.right;
  }

  getChartInnerHeight(): number {
    return this.chartHeight - this.chartPadding.top - this.chartPadding.bottom;
  }

  getMaxY(): number {
    if (!this.dailyData.length) return 100;
    const maxLogged = Math.max(...this.dailyData.map(d => d.logged));
    const maxTarget = Math.max(...this.dailyData.map(d => d.target));
    return Math.max(maxLogged, maxTarget, 100);
  }

  getPointX(index: number): number {
    const innerWidth = this.getChartInnerWidth();
    const spacing = innerWidth / (this.dailyData.length - 1 || 1);
    return this.chartPadding.left + (index * spacing);
  }

  getPointY(value: number): number {
    const innerHeight = this.getChartInnerHeight();
    const maxY = this.getMaxY();
    const ratio = value / maxY;
    return this.chartPadding.top + (innerHeight * (1 - ratio));
  }

  getLinePath(): string {
    if (!this.dailyData.length) return '';

    const points = this.dailyData.map((d, i) => {
      const x = this.getPointX(i);
      const y = this.getPointY(d.logged);
      return `${i === 0 ? 'M' : 'L'} ${x} ${y}`;
    });

    return points.join(' ');
  }

  getTargetLinePath(): string {
    if (!this.dailyData.length) return '';

    const points = this.dailyData.map((d, i) => {
      const x = this.getPointX(i);
      const y = this.getPointY(d.target);
      return `${i === 0 ? 'M' : 'L'} ${x} ${y}`;
    });

    return points.join(' ');
  }

  formatDate(dateStr: string): string {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }

  getYAxisLabels(): number[] {
    const maxY = this.getMaxY();
    const step = Math.ceil(maxY / 5);
    return [0, step, step * 2, step * 3, step * 4, maxY];
  }

  // Tooltip State
  selectedPoint: any = null;
  tooltipPosition = { x: 0, y: 0 };
  private tooltipOffset = 15; // Gap between cursor and tooltip

  onPointHover(point: any, event: MouseEvent) {
    this.selectedPoint = point;
    this.updateTooltipPosition(event);
  }

  onPointLeave() {
    this.selectedPoint = null;
  }

  updateTooltipPosition(event: MouseEvent) {
    // Get mouse coordinates relative to the viewport
    // But we need them relative to the chart-container for absolute positioning
    // However, event.offsetX/Y are relative to the target (circle), which is small.
    // Using a simpler approach: Track mousemove on the container or use pageX/Y minus container offset.
    // Simpler yet for Angular: use the chart container's bounding rect.

    // Actually, simply using the point's chart coordinates is more stable than mouse, 
    // effectively "snapping" the tooltip to the point, but we can offset it based on available space.

    if (!this.selectedPoint) return;

    // Use the calculated chart coordinates
    const index = this.dailyData.indexOf(this.selectedPoint);
    const chartX = this.getPointX(index);
    const chartY = this.getPointY(this.selectedPoint.logged);

    // Default: Top Center
    // We'll let CSS handle the transform centering, but we decide Top vs Bottom here if needed.
    // Since the issue is "top side header side output hide", we should check if y is too low.

    // Move tooltip slightly above the point
    this.tooltipPosition = {
      x: chartX,
      y: chartY - 20
    };

    // If point is very high (close to top content edge), show tooltip BELOW instead
    // Approximate check: chartY < 80 (since top padding is 40)
    if (chartY < 80) {
      // Ideally we'd set a flag for CSS class 'bottom', but for now let's just push Y down
      // But pushing Y down means we need to change CSS transform to translate(-50%, 10px) instead of -130%
      // To do this properly, let's just pass the raw coordinates and handle logic in template/css 
      // Or, ensure Y never goes below a certain visual threshold relative to container.
    }
  }

  // To fix the "header hides tooltip" issue specifically:
  // We can attach a class [class.tooltip-bottom]="isTooltipBottom"
  isTooltipBottom = false;
  isTooltipRight = false; // Add this

  onPointEnter(point: any) {
    this.selectedPoint = point;
    const index = this.dailyData.indexOf(point);
    const x = this.getPointX(index);
    const y = this.getPointY(point.logged);

    this.tooltipPosition = {
      x: x,
      y: y
    };

    // If Y is too close to top (e.g. < 100px), flip to bottom
    this.isTooltipBottom = y < 100;

    // If X is too close to left (e.g. < 100px), shift anchor
    this.isTooltipRight = x < 100;
  }

  goBack() {
    this.router.navigate(['/plans']);
  }
}
