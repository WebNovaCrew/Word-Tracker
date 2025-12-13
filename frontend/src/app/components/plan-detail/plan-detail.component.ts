import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { ContentLoaderComponent } from '../content-loader/content-loader.component';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-plan-detail',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, ContentLoaderComponent],
  templateUrl: './plan-detail.component.html',
  styleUrls: ['./plan-detail.component.scss']
})
export class PlanDetailComponent implements OnInit {
  plan: any;
  loading = true;

  constructor(
    private route: ActivatedRoute,
    private http: HttpClient
  ) { }

  ngOnInit() {
    const id = this.route.snapshot.paramMap.get('id');
    this.loadPlan(id || '1'); // Mock load
  }

  loadPlan(id: string) {
    this.loading = true;
    this.http.get<any>(`${environment.apiUrl}/get_plan_full_details.php?id=` + id)
      .subscribe({
        next: (data) => {
          this.plan = data;
          this.loading = false;
          console.log('Plan Details Loaded:', this.plan);
        },
        error: (err) => {
          console.error('Failed to load plan', err);
          alert(err.status === 0 ? 'Network error. Please check your connection.' : 'Error loading plan details');
          this.loading = false;
        }
      });
  }

  updateProgress(day: any, newValue: any) {
    const val = parseInt(newValue, 10);
    if (isNaN(val)) return;

    this.http.post(`${environment.apiUrl}/add_progress.php`, {
      plan_id: this.plan.id,
      date: day.date,
      count: val
    }).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.loadPlan(this.plan.id);
        }
      },
      error: (e) => {
        console.error(e);
        alert(e.status === 0 ? 'Network error' : 'Failed to update progress');
      }
    });
  }
}
