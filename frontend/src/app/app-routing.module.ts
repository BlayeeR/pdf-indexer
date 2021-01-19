import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { AddDocumentComponent } from './add-document/add-document.component';
import { DocumentsComponent } from './documents/documents.component';


const routes: Routes = [
  { path: 'documents', component: DocumentsComponent},
  { path: 'add-document', component: AddDocumentComponent},
  { path: '', redirectTo: '/documents', pathMatch: 'full'}
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
