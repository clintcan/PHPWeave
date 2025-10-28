# PHPWeave Kubernetes Deployment (v1.29+)

Production-ready Kubernetes manifests with latest features and security best practices.

## Prerequisites
- Kubernetes cluster (1.29+ required, 1.31+ recommended)
- kubectl configured
- NGINX Ingress Controller
- Metrics Server (for HPA)
- cert-manager (for SSL certificates)

## Quick Deploy

```bash
# 1. Create namespace
kubectl create namespace phpweave

# 2. Update secrets
cp k8s/secrets.env.example k8s/secrets.env
# Edit k8s/secrets.env with your credentials

# 3. Update domain
# Edit k8s/ingress.yaml - replace phpweave.example.com

# 4. Deploy with Kustomize
kubectl apply -k k8s/

# 5. Verify deployment
kubectl get pods -n phpweave
kubectl get ingress -n phpweave
kubectl get hpa -n phpweave
```

## Files

### Core Application
- `deployment.yaml` - PHPWeave app with security contexts, probes, and affinity rules
- `service.yaml` - ClusterIP service with dual-stack IPv4/IPv6 support
- `ingress.yaml` - NGINX ingress with SSL, security headers, rate limiting, CORS
- `hpa.yaml` - HorizontalPodAutoscaler with CPU, memory, and custom metrics
- `configmap.yaml` - Extended configuration with performance and security settings
- `secret.yaml` - Credentials, API keys, and encryption keys
- `pvc.yaml` - Persistent volume claim with expansion support

### Database
- `mysql.yaml` - MySQL StatefulSet with init container, Prometheus exporter, and tuning

### Security & Reliability  
- `networkpolicy.yaml` - Pod network isolation and traffic control
- `poddisruptionbudget.yaml` - Availability guarantees during updates
- `servicemonitor.yaml` - Prometheus metrics collection

### Configuration
- `kustomization.yaml` - Kustomize for GitOps and environment overlays
- `health.php` - Health check endpoint

## New Features (K8s 1.29+)

1. **Security Enhancements**:
   - Pod security contexts with non-root users
   - Seccomp profiles and dropped capabilities
   - Network policies for traffic isolation
   - Security headers in ingress

2. **High Availability**:
   - Pod disruption budgets
   - Anti-affinity rules
   - Topology spread constraints
   - Startup probes

3. **Performance**:
   - HPA performance profile (1.31+)
   - Multiple metric types for scaling
   - MySQL performance tuning
   - Resource limits for ephemeral storage

4. **Observability**:
   - Prometheus ServiceMonitor
   - MySQL exporter sidecar
   - Enhanced health checks

## Important Notes

1. **Update these before deploying**:
   - Image repository in `kustomization.yaml`
   - Passwords in `secret.yaml` and `mysql.yaml`
   - Domain in `ingress.yaml`
   - Storage class in PVC files

2. **Version Requirements**:
   - Minimum: Kubernetes 1.29
   - Recommended: Kubernetes 1.31+ (for HPA performance profile)
   - Required features: Metrics Server, NGINX Ingress, cert-manager

3. **Monitoring**: 
   - Prometheus Operator recommended for ServiceMonitor support
   - Metrics exposed on `/metrics` endpoint

See `docs/KUBERNETES_DEPLOYMENT.md` for complete deployment guide.