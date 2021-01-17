import { Component, OnInit } from '@angular/core';
import { NavigationEnd, Router } from '@angular/router';
import { Subscription } from 'rxjs';
import { MainService } from '../main.service';

@Component({
  selector: 'app-menu',
  templateUrl: './menu.component.html',
  styleUrls: ['./menu.component.scss']
})
export class MenuComponent implements OnInit {

  private routerEventSubscription: Subscription;
  public activeRoute: string;

  constructor(private router: Router, public mainService: MainService) {
  }

  ngOnDestroy() {
    this.routerEventSubscription.unsubscribe();
  }

  ngOnInit(): void {
    this.routerEventSubscription = this.router.events.subscribe((event: NavigationEnd) => {
      if(event.url) {
        const option = this.mainService.menuOptions.find(x=>x.Route === event.url);
        if(option) {
          this.activeRoute = option.Name;
        }
      }
    });
  }

}
