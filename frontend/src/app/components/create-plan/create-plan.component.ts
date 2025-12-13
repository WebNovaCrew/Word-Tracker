import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-create-plan',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  templateUrl: './create-plan.component.html',
  styleUrls: ['./create-plan.component.scss']
})
export class CreatePlanComponent implements OnInit {
  planForm: FormGroup;
  today: string;
  isSubmitting = false;
  errorMessage = '';

  constructor(
    private fb: FormBuilder,
    private http: HttpClient,
    private router: Router
  ) {
    // Get today's date in YYYY-MM-DD format for min attribute
    const now = new Date();
    this.today = now.toISOString().split('T')[0];

    this.planForm = this.fb.group({
      title: ['', Validators.required],
      total_word_count: [50000, [Validators.required, Validators.min(1)]],
      start_date: ['', Validators.required],
      end_date: ['', Validators.required],
      algorithm_type: ['steady', Validators.required]
    });
  }

  ngOnInit() {
    // Set default start date to today
    this.planForm.patchValue({
      start_date: this.today
    });

    // Watch for start date changes to validate end date
    this.planForm.get('start_date')?.valueChanges.subscribe(startDate => {
      const endDate = this.planForm.get('end_date')?.value;
      if (endDate && startDate && new Date(endDate) <= new Date(startDate)) {
        this.planForm.patchValue({ end_date: '' }, { emitEvent: false });
      }
    });
  }

  // Helper to parse "YYYY-MM-DD" to local Date object
  private parseDate(dateStr: string): Date {
    const [y, m, d] = dateStr.split('-').map(Number);
    return new Date(y, m - 1, d);
  }

  calculateDays(): number {
    const startDate = this.planForm.get('start_date')?.value;
    const endDate = this.planForm.get('end_date')?.value;

    if (!startDate || !endDate) {
      return 0;
    }

    const start = this.parseDate(startDate);
    const end = this.parseDate(endDate);
    const diffTime = Math.abs(end.getTime() - start.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end day

    return diffDays;
  }

  calculateDailyGoal(): number {
    const days = this.calculateDays();
    const totalGoal = this.planForm.get('total_word_count')?.value || 0;

    if (days === 0) return 0;

    return Math.ceil(totalGoal / days);
  }

  showCalendarPreview(): boolean {
    const startDate = this.planForm.get('start_date')?.value;
    const endDate = this.planForm.get('end_date')?.value;

    return !!(startDate && endDate);
  }

  get calendarDays(): any[] {
    const startDate = this.planForm.get('start_date')?.value;
    const endDate = this.planForm.get('end_date')?.value;

    if (!startDate || !endDate) {
      return [];
    }

    const start = this.parseDate(startDate);
    const end = this.parseDate(endDate);
    const days: any[] = [];

    // Get the first day of the month and last day of the month
    const firstDayOfMonth = new Date(start.getFullYear(), start.getMonth(), 1);
    const lastDayOfMonth = new Date(end.getFullYear(), end.getMonth() + 1, 0);

    // Add empty cells for days before the start of the month
    const startDayOfWeek = firstDayOfMonth.getDay();
    for (let i = 0; i < startDayOfWeek; i++) {
      days.push({ date: null, displayDay: '', isEmpty: true });
    }

    // Add all days in the range
    const currentDate = new Date(firstDayOfMonth);

    // Safety break to prevent infinite loops
    const maxIterations = 730; // 2 years
    let i = 0;

    while (currentDate <= lastDayOfMonth && i < maxIterations) {
      const currentTs = currentDate.getTime();
      const startTs = start.getTime();
      const endTs = end.getTime();

      const isPlanDay = currentTs >= startTs && currentTs <= endTs;
      const isStart = currentTs === startTs;
      const isEnd = currentTs === endTs;

      let tooltip = '';
      if (isStart) tooltip = 'Start Date';
      else if (isEnd) tooltip = 'End Date';
      else if (isPlanDay) tooltip = 'Writing Day';

      days.push({
        date: new Date(currentDate),
        displayDay: currentDate.getDate(),
        isPlanDay,
        isStart,
        isEnd,
        tooltip
      });

      currentDate.setDate(currentDate.getDate() + 1);
      i++;
    }

    return days;
  }

  onSubmit() {
    if (this.planForm.invalid) {
      this.errorMessage = 'Please fill in all required fields.';
      return;
    }

    // Validate date range
    const startDate = new Date(this.planForm.value.start_date);
    const endDate = new Date(this.planForm.value.end_date);

    if (endDate <= startDate) {
      this.errorMessage = 'End date must be after start date.';
      return;
    }

    this.isSubmitting = true;
    this.errorMessage = '';

    // Map form fields to API expected field names
    const payload = {
      user_id: localStorage.getItem('user_id'),
      name: this.planForm.value.title,
      goal_amount: this.planForm.value.total_word_count,
      start_date: this.planForm.value.start_date,
      end_date: this.planForm.value.end_date,
      strategy: this.planForm.value.algorithm_type,
      content_type: 'Novel',
      activity_type: 'Writing',
      intensity: 'average'
    };

    console.log('Sending plan data:', payload);

    this.http.post(`${environment.apiUrl}/create_plan.php`, payload)
      .subscribe({
        next: (response) => {
          console.log('Plan created successfully:', response);
          this.isSubmitting = false;
          this.router.navigate(['/dashboard']);
        },
        error: (err) => {
          console.error('Error creating plan:', err);
          this.isSubmitting = false;
          this.errorMessage = err.status === 0
            ? 'Network error. Please check your connection.'
            : 'Failed to create plan. Please try again.';
        }
      });
  }
}
